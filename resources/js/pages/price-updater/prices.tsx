import React, { useEffect, useState } from 'react';
import { AppHeader } from '@/components/app-header';
import { router, Head } from '@inertiajs/react';
import SteeringDataTable, { SteeringRecord } from '@/components/price-updater/steering-data-table';

interface PricesPageProps {
	entryData: string;
}

interface DataItem {
	name: string;
	start: string;
	end: string;
	yield: string;
	yield_s: string;
	price: string;
	Lor: number;
	Mlor: number;
	peaks: Array<{
		start: string;
		end: string;
		yield: string;
		yield_s: string;
		price: string;
		Lor: number;
		Mlor: number;
	}>;
}

interface ParsedData {
	date: string;
	location: string;
	data: DataItem[];
}

interface ApiResponse {
	prices?: {
		steerings?: SteeringRecord[];
		[key: string]: unknown;
	};
	error?: string;
}

interface PublishResponse {
	message?: string;
	response?: unknown;
	error?: string;
}

interface PublishRecord {
	location_level: string;
	location_id: string;
	steer_type: string;
	length_of_rent_from: number;
	length_of_rent_to: number;
	vehicle_type: string;
	vehicle_group: string;
	yield_type: string;
	value_type: string;
	value: number;
	steer_from: string;
	steer_to: string;
	identity: string;
	channel: string;
	available_type: string;
	remark: string;
	operation: string;
}

const PricesPage: React.FC<PricesPageProps> = ({ entryData }) => {
	const [prices, setPrices] = useState<Record<string, unknown> | null>(null);
	const [error, setError] = useState<string | null>(null);
	const [loading, setLoading] = useState(true);
	const [publishing, setPublishing] = useState(false);
	const [publishResult, setPublishResult] = useState<PublishResponse | null>(null);
	const [processedRowsCount, setProcessedRowsCount] = useState<number | null>(null);
	const [refreshingData, setRefreshingData] = useState(false);

	// Function to parse date from DD/MM/YYYY format to YYYY-MM-DD
	const parseDate = (dateStr: string): string => {
		const [day, month, year] = dateStr.split('/');
		return `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
	};

	// Function to analyze data and extract location and date range
	const analyzeData = React.useCallback((data: ParsedData) => {
		const location = data.location;

		// Collect all dates from main data and peaks
		const allDates: string[] = [];

		// Add dates from main data items
		data.data.forEach((item) => {
			allDates.push(item.start);
			allDates.push(item.end);

			// Add dates from peaks
			item.peaks.forEach((peak) => {
				allDates.push(peak.start);
				allDates.push(peak.end);
			});
		});

		// Parse all dates and find min/max
		const parsedDates = allDates.map((date) => new Date(parseDate(date)));
		const minDate = new Date(Math.min(...parsedDates.map((d) => d.getTime())));
		const maxDate = new Date(Math.max(...parsedDates.map((d) => d.getTime())));

		// Format dates for API (YYYY-MM-DD HH:mm)
		const steerFrom = `${minDate.getFullYear()}-${String(minDate.getMonth() + 1).padStart(2, '0')}-${String(minDate.getDate()).padStart(2, '0')} 00:00`;
		const steerTo = `${maxDate.getFullYear()}-${String(maxDate.getMonth() + 1).padStart(2, '0')}-${String(maxDate.getDate()).padStart(2, '0')} 23:59`;
		console.log('steerFrom', steerFrom);
		console.log('steerTo', steerTo);
		return {
			location_id: location,
			location_level: 'LOCATION_LEVEL_BRANCH',
			steer_from: steerFrom,
			steer_to: steerTo,
			limit: 1000,
			offset: 0,
		};
	}, []);

	// Function to convert entry data to publish format
	const convertToPublishFormat = (parsedData: ParsedData): PublishRecord[] => {
		const records: PublishRecord[] = [];

		parsedData.data.forEach((item) => {
			// Main period
			records.push({
				location_level: 'LOCATION_LEVEL_BRANCH',
				location_id: parsedData.location,
				steer_type: 'STEER_TYPE_UDA',
				length_of_rent_from: 1,
				length_of_rent_to: 1,
				vehicle_type: 'VEHICLE_TYPE_P',
				vehicle_group: item.name,
				yield_type: 'TYPE_LEVEL_PLAIN',
				value_type: 'VALUE_TYPE_RATE_P',
				value: parseFloat(item.yield) || 0,
				steer_from: parseDate(item.start) + ' 00:00',
				steer_to: parseDate(item.end) + ' 23:59',
				identity: 'franchise',
				channel: 'GIVO',
				available_type: 'AVAILABLE_TYPE_CONDITIONAL',
				remark: `Steering ${item.name}`,
				operation: 'UPSERT_WITH_STEER_PERIOD_SPLIT',
			});

			// Peaks
			item.peaks.forEach((peak) => {
				records.push({
					location_level: 'LOCATION_LEVEL_BRANCH',
					location_id: parsedData.location,
					steer_type: 'STEER_TYPE_PEAK',
					length_of_rent_from: 1,
					length_of_rent_to: 1,
					vehicle_type: 'VEHICLE_TYPE_P',
					vehicle_group: item.name,
					yield_type: 'TYPE_LEVEL_PLAIN',
					value_type: 'VALUE_TYPE_RATE_P',
					value: parseFloat(peak.yield) || 0,
					steer_from: parseDate(peak.start) + ' 00:00',
					steer_to: parseDate(peak.end) + ' 23:59',
					identity: 'franchise',
					channel: 'GIVO',
					available_type: 'AVAILABLE_TYPE_CONDITIONAL',
					remark: `Peak ${item.name}`,
					operation: 'UPSERT_WITH_STEER_PERIOD_SPLIT',
				});
			});
		});

		return records;
	};

	// Function to refresh prices data
	const refreshPrices = async () => {
		setRefreshingData(true);
		setError(null);

		try {
			// Parse the entry data
			const parsedData: ParsedData = JSON.parse(entryData) as ParsedData;

			// Analyze the data to extract location and date range
			const apiPayload = analyzeData(parsedData);

			const response = await fetch('/price-updater/fetch-prices', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-CSRF-TOKEN':
						(document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)
							?.content || '',
				},
				body: JSON.stringify({ data: JSON.stringify(apiPayload) }),
			});

			const result: ApiResponse = (await response.json()) as ApiResponse;
			if (!response.ok) {
				setError(result.error || 'Failed to fetch prices');
			} else {
				setPrices(result.prices || null);
			}
		} catch (parseError) {
			setError('Failed to parse entry data');
			console.error('Parse error:', parseError);
		} finally {
			setRefreshingData(false);
		}
	};

	// Function to publish steering records
	const handlePublish = async () => {
		setPublishing(true);
		setPublishResult(null);
		setError(null);
		setProcessedRowsCount(null);

		try {
			const parsedData: ParsedData = JSON.parse(entryData) as ParsedData;
			const publishRecords = {
				steerings: convertToPublishFormat(parsedData),
			};

			console.log('publishRecords', JSON.stringify(publishRecords, null, 2));

			const response = await fetch('/price-updater/publish-prices', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-CSRF-TOKEN':
						(document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)
							?.content || '',
				},
				body: JSON.stringify({ data: JSON.stringify(publishRecords) }),
			});

			const result: PublishResponse = (await response.json()) as PublishResponse;

			if (!response.ok) {
				setError(result.error || 'Failed to publish prices');
			} else {
				setPublishResult(result);
				// Set the count of processed rows
				setProcessedRowsCount(publishRecords.steerings.length);

				// Automatically refresh the data after successful publishing
				setRefreshingData(true);
				await refreshPrices();
			}
		} catch (publishError) {
			setError('Failed to publish steering records');
			console.error('Publish error:', publishError);
		} finally {
			setPublishing(false);
			setRefreshingData(false);
		}
	};

	useEffect(() => {
		async function fetchPrices() {
			setLoading(true);
			setError(null);
			setPrices(null);

			try {
				// Parse the entry data
				const parsedData: ParsedData = JSON.parse(entryData) as ParsedData;

				// Analyze the data to extract location and date range
				const apiPayload = analyzeData(parsedData);

				const response = await fetch('/price-updater/fetch-prices', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-CSRF-TOKEN':
							(document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)
								?.content || '',
					},
					body: JSON.stringify({ data: JSON.stringify(apiPayload) }),
				});

				const result: ApiResponse = (await response.json()) as ApiResponse;
				if (!response.ok) {
					setError(result.error || 'Failed to fetch prices');
				} else {
					setPrices(result.prices || null);
				}
			} catch (parseError) {
				setError('Failed to parse entry data');
				console.error('Parse error:', parseError);
			} finally {
				setLoading(false);
			}
		}

		void fetchPrices();
	}, [entryData, analyzeData]);

	// Extract steering records from prices data
	const steeringRecords =
		prices && 'steerings' in prices && Array.isArray(prices.steerings)
			? (prices.steerings as SteeringRecord[])
			: [];

	// Helper to map entryData to SteeringRecord[]
	const mapEntryDataToSteeringRecords = (parsedData: ParsedData): SteeringRecord[] => {
		const baseFields = {
			location_id: parsedData.location,
			location_level: 'LOCATION_LEVEL_POOL',
			channel: '-',
			direction: '-',
			rate_plan: '-',
			rate_code: '-',
			available_type: '-',
			point_of_sale_location: '-',
			stable: false,
			identity: '-',
			remark: '-',
			created_at: '',
		};

		const records: SteeringRecord[] = [];

		parsedData.data.forEach((item) => {
			// Main period
			records.push({
				id: '',
				steer_type: 'STEER_TYPE_UDA',
				steer_from: parseDate(item.start) + ' 00:00',
				steer_to: parseDate(item.end) + ' 23:59',
				length_of_rent_from: 1,
				length_of_rent_to: 1,
				vehicle_group: item.name,
				value: item.yield || '-',
				...baseFields,
			});
			// Peaks
			item.peaks.forEach((peak) => {
				records.push({
					id: '',
					steer_type: 'STEER_TYPE_PEAK',
					steer_from: parseDate(peak.start) + ' 00:00',
					steer_to: parseDate(peak.end) + ' 23:59',
					length_of_rent_from: 1,
					length_of_rent_to: 1,
					vehicle_group: item.name,
					value: peak.yield || '-',
					...baseFields,
				});
			});
		});
		return records;
	};

	// Extract steering records from entryData
	let entryDataRecords: SteeringRecord[] = [];
	try {
		const parsedEntryData: ParsedData = JSON.parse(entryData) as ParsedData;
		entryDataRecords = mapEntryDataToSteeringRecords(parsedEntryData);
	} catch (parseError) {
		console.error('Failed to parse entry data', parseError);
	}

	return (
		<div className="min-h-screen bg-gray-50">
			<Head title="Fetched Prices" />
			<AppHeader />
			<div className="max-w-11/12 sm:max-w-11/12 md:max-w-11/12 lg:max-w-11/12 mx-auto mt-10 w-full">
				<div className="mb-4">
					<button
						onClick={() => router.visit('/price-updater/data-entry')}
						className="flex items-center gap-2 text-gray-600 transition-colors hover:cursor-pointer hover:text-gray-900"
					>
						<svg
							className="h-5 w-5"
							fill="none"
							stroke="currentColor"
							viewBox="0 0 24 24"
						>
							<path
								strokeLinecap="round"
								strokeLinejoin="round"
								strokeWidth={2}
								d="M10 19l-7-7m0 0l7-7m-7 7h18"
							/>
						</svg>
						Back to Data Entry
					</button>
				</div>
				<div className="rounded bg-white p-6 shadow">
					{(() => {
						try {
							const parsedData: ParsedData = JSON.parse(entryData) as ParsedData;
							return (
								<div className="mb-6 border-b border-gray-200 pb-4">
									<h1 className="text-2xl font-bold text-gray-900">
										Steering Records
									</h1>
									<h2 className="mt-2 text-lg text-gray-600">
										Location: {parsedData.location} | Date: {parsedData.date}
									</h2>
								</div>
							);
						} catch {
							return <h1 className="mb-6 text-2xl font-bold">Fetched Prices</h1>;
						}
					})()}
					{loading && (
						<div className="flex items-center gap-2">
							<div className="h-4 w-4 animate-spin rounded-full border-2 border-blue-600 border-t-transparent"></div>
							<span>Loading initial data...</span>
						</div>
					)}
					{error && <div className="mb-5 text-red-600">{error}</div>}
					{publishResult && (
						<div className="mb-5 rounded bg-green-50 p-4 text-green-800">
							<h3 className="font-semibold">Success!</h3>
							<p>
								{String(publishResult.message || 'Prices published successfully')}
							</p>
							{processedRowsCount !== null && (
								<p className="mt-2 text-sm">
									<strong>{processedRowsCount}</strong> steering records were
									processed by the external API.
								</p>
							)}
							{refreshingData && (
								<div className="mt-3 flex items-center gap-2 text-sm">
									<div className="h-4 w-4 animate-spin rounded-full border-2 border-green-600 border-t-transparent"></div>
									<span>Refreshing data...</span>
								</div>
							)}
							{publishResult.response !== undefined && (
								<details className="mt-2">
									<summary className="cursor-pointer text-sm">
										View Response Details
									</summary>
									<pre className="mt-2 overflow-x-auto whitespace-pre-wrap break-all rounded border bg-green-100 p-2 text-xs">
										{JSON.stringify(publishResult.response, null, 2)}
									</pre>
								</details>
							)}
						</div>
					)}
					{prices && (
						<>
							{steeringRecords.length > 0 ? (
								<div className="mt-10">
									<div className="mb-4 flex items-center justify-between">
										<h3 className="text-lg font-semibold text-gray-900">
											Current Steering Records ({steeringRecords.length})
										</h3>
										<button
											onClick={() => void refreshPrices()}
											disabled={refreshingData}
											className="flex items-center gap-2 rounded bg-gray-600 px-3 py-1 text-sm text-white transition-colors hover:bg-gray-700 disabled:cursor-not-allowed disabled:bg-gray-400"
										>
											{refreshingData ? (
												<>
													<div className="h-3 w-3 animate-spin rounded-full border border-white border-t-transparent"></div>
													Refreshing...
												</>
											) : (
												<>
													<svg
														className="h-3 w-3"
														fill="none"
														stroke="currentColor"
														viewBox="0 0 24 24"
													>
														<path
															strokeLinecap="round"
															strokeLinejoin="round"
															strokeWidth={2}
															d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
														/>
													</svg>
													Refresh
												</>
											)}
										</button>
									</div>
									<SteeringDataTable
										steerings={steeringRecords}
										id="current-steering-records"
									/>
								</div>
							) : (
								<div className="mb-6">
									<h3 className="mb-4 text-lg font-semibold text-gray-900">
										Raw API Response
									</h3>
									<pre className="overflow-x-auto whitespace-pre-wrap break-all rounded border bg-gray-100 p-4 text-xs">
										{JSON.stringify(prices, null, 2)}
									</pre>
								</div>
							)}
							{entryDataRecords.length > 0 && (
								<div className="mt-10">
									<div className="mb-4 flex items-center justify-between">
										<h3 className="text-lg font-semibold text-gray-900">
											New Steering Records to be added (
											{entryDataRecords.length})
										</h3>
										<button
											onClick={() => void handlePublish()}
											disabled={publishing}
											className="rounded bg-blue-600 px-4 py-2 text-white transition-colors hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-gray-400"
										>
											{publishing ? 'Publishing...' : 'SAVE'}
										</button>
									</div>
									<SteeringDataTable
										steerings={entryDataRecords}
										id="new-steering-records"
									/>
								</div>
							)}
						</>
					)}
				</div>
			</div>
		</div>
	);
};

export default PricesPage;
