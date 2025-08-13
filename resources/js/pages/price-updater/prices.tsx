import React, { useEffect, useState, useCallback } from 'react';
import { AppHeader } from '@/components/app-header';
import { router, Head } from '@inertiajs/react';
import SteeringDataTable from '@/components/price-updater/steering-data-table';
import {
	VehiclePricesService,
	ParsedData,
	PublishResponse,
	SteeringRecord,
} from '@/services/VehiclePricesService';

interface PricesPageProps {
	entryData: string;
}

const PricesPage: React.FC<PricesPageProps> = ({ entryData }) => {
	const [prices, setPrices] = useState<Record<string, unknown> | null>(null);
	const [error, setError] = useState<string | null>(null);
	const [loading, setLoading] = useState(true);
	const [publishing, setPublishing] = useState(false);
	const [deleting, setDeleting] = useState(false);
	const [publishResult, setPublishResult] = useState<PublishResponse | null>(null);
	const [deleteResult, setDeleteResult] = useState<PublishResponse | null>(null);
	const [processedRowsCount, setProcessedRowsCount] = useState<number | null>(null);
	const [refreshingData, setRefreshingData] = useState(false);
	const [selectedCurrentRows, setSelectedCurrentRows] = useState<Set<string>>(new Set());

	// Function to handle row selection for current steering records
	const handleCurrentRowSelect = (rowId: string) => {
		setSelectedCurrentRows((prev) => {
			const newSet = new Set(prev);
			if (newSet.has(rowId)) {
				newSet.delete(rowId);
			} else {
				newSet.add(rowId);
			}
			return newSet;
		});
	};

	// Function to select all current steering records
	const handleSelectAllCurrent = () => {
		const allRowIds = steeringRecords.map(
			(_, index) => `current-steering-records_${_.id || index}`
		);
		setSelectedCurrentRows(new Set(allRowIds));
	};

	// Function to clear all selections
	const handleClearSelections = () => {
		setSelectedCurrentRows(new Set());
	};

	// Function to handle delete selected records
	const handleDeleteSelected = async () => {
		setDeleting(true);
		setDeleteResult(null);
		setPublishResult(null);
		setError(null);
		setProcessedRowsCount(null);

		try {
			// Get the selected steering records
			const selectedRecords = steeringRecords.filter((_, index) =>
				selectedCurrentRows.has(`current-steering-records_${_.id || index}`)
			);

			const {
				result,
				error: deleteError,
				processedRowsCount: count,
			} = await VehiclePricesService.deletePrices(selectedRecords);

			if (deleteError) {
				setError(deleteError);
			} else {
				setDeleteResult(result);
				setProcessedRowsCount(count);
				setSelectedCurrentRows(new Set()); // Clear selections after successful deletetion

				// Show the refreshing indicator immediately
				setRefreshingData(true);

				// Wait 3 seconds while showing the loader
				await new Promise((resolve) => setTimeout(resolve, 3000));

				// Automatically refresh the data after successful deletion
				await fetchPricesData(true);
			}
		} catch (error) {
			setError('Failed to delete steering records');
			console.error('Delete error:', error);
		} finally {
			setDeleting(false);
		}
	};

	// Unified function to fetch prices data
	const fetchPricesData = useCallback(
		async (isRefreshing = false) => {
			console.log('fetchPricesData', isRefreshing);
			if (isRefreshing) {
				setRefreshingData(true);
			} else {
				setLoading(true);
			}
			setError(null);

			try {
				const result = await VehiclePricesService.fetchPrices(entryData);
				console.log('result', result);

				if (result.error) {
					setError(result.error);
				} else {
					setPrices(result.prices);
				}
			} catch (error) {
				setError('Failed to fetch prices');
				console.error('Fetch error:', error);
			} finally {
				if (isRefreshing) {
					setRefreshingData(false);
				} else {
					setLoading(false);
				}
			}
		},
		[entryData]
	);

	// Function to refresh prices data
	const refreshPrices = async () => {
		await fetchPricesData(true);
	};

	// Function to publish steering records
	const handlePublish = async () => {
		setPublishing(true);
		setPublishResult(null);
		setDeleteResult(null);
		setError(null);
		setProcessedRowsCount(null);

		try {
			const {
				result,
				error: publishError,
				processedRowsCount: count,
			} = await VehiclePricesService.publishPrices(entryData);

			if (publishError) {
				setError(publishError);
			} else {
				setPublishResult(result);
				setProcessedRowsCount(count);

				// Show the refreshing indicator immediately
				setRefreshingData(true);

				// Wait 3 seconds while showing the loader
				await new Promise((resolve) => setTimeout(resolve, 3000));

				// Automatically refresh the data after successful publishing
				await fetchPricesData(true);
			}
		} catch (error) {
			setError('Failed to publish steering records');
			console.error('Publish error:', error);
		} finally {
			setPublishing(false);
		}
	};

	useEffect(() => {
		void fetchPricesData(false);
	}, [entryData, fetchPricesData]);

	// Extract steering records from prices data
	const steeringRecords =
		prices && 'steerings' in prices && Array.isArray(prices.steerings)
			? (prices.steerings as SteeringRecord[])
			: [];

	// Extract steering records from entryData using the service
	const { records: entryDataRecords } =
		VehiclePricesService.parseEntryDataToSteeringRecords(entryData);

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
					{deleteResult && (
						<div className="mb-5 rounded bg-green-50 p-4 text-green-800">
							<h3 className="font-semibold">Success!</h3>
							<p>{String(deleteResult.message || 'Records deleted successfully')}</p>
							{processedRowsCount !== null && (
								<p className="mt-2 text-sm">
									<strong>{processedRowsCount}</strong> steering records were
									deleted by the external API.
								</p>
							)}
							{refreshingData && (
								<div className="mt-3 flex items-center gap-2 text-base font-medium">
									<div className="h-5 w-5 animate-spin rounded-full border-2 border-green-600 border-t-transparent"></div>
									<span>Refreshing data...</span>
								</div>
							)}
						</div>
					)}
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
								<div className="mt-3 flex items-center gap-2 text-base font-medium">
									<div className="h-5 w-5 animate-spin rounded-full border-2 border-green-600 border-t-transparent"></div>
									<span>Refreshing data...</span>
								</div>
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
											{selectedCurrentRows.size > 0 && (
												<span className="ml-2 text-sm text-red-600">
													({selectedCurrentRows.size} selected for
													deletion)
												</span>
											)}
										</h3>
										<div className="flex items-center gap-2">
											<button
												onClick={handleSelectAllCurrent}
												className="rounded bg-blue-600 px-3 py-1 text-sm text-white transition-colors hover:cursor-pointer hover:bg-blue-700"
											>
												Select All
											</button>
											<br />
											{selectedCurrentRows.size > 0 && (
												<>
													<button
														onClick={handleClearSelections}
														className="rounded bg-gray-500 px-3 py-1 text-sm text-white transition-colors hover:cursor-pointer hover:bg-gray-600"
													>
														Clear Selection
													</button>
													<button
														onClick={() => void handleDeleteSelected()}
														disabled={deleting}
														id="delete-selected-button"
														className="rounded bg-red-600 px-3 py-1 text-sm text-white transition-colors hover:cursor-pointer hover:bg-red-700 disabled:cursor-not-allowed disabled:bg-gray-400"
													>
														{deleting ? (
															<>
																<div className="loader-icon h-4 w-4 animate-spin rounded-full border border-white border-t-transparent"></div>
																Deleting...
															</>
														) : (
															<>
																<svg
																	className="delete-icon h-4 w-4"
																	fill="none"
																	stroke="currentColor"
																	viewBox="0 0 24 24"
																>
																	<path
																		strokeLinecap="round"
																		strokeLinejoin="round"
																		strokeWidth={2}
																		d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
																	/>
																</svg>
																Delete Selected (
																{selectedCurrentRows.size})
															</>
														)}
													</button>
												</>
											)}
											<button
												onClick={() => void refreshPrices()}
												disabled={refreshingData}
												className="flex items-center gap-2 rounded bg-gray-600 px-3 py-1 text-sm text-white transition-colors hover:cursor-pointer hover:bg-gray-700 disabled:cursor-not-allowed disabled:bg-gray-400"
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
									</div>
									<SteeringDataTable
										steerings={steeringRecords}
										id="current-steering-records"
										selectedRows={selectedCurrentRows}
										onRowSelect={handleCurrentRowSelect}
										onSelectAll={handleSelectAllCurrent}
										showSelectionControls={true}
									/>
								</div>
							) : (
								<div className="mb-6">
									<div className="rounded bg-blue-50 p-4 text-blue-800">
										<h3 className="mb-2 text-lg font-semibold">
											No Current Steering Records
										</h3>
										<p className="text-sm">
											No existing steering records were found for the
											specified location and date range. You can add new
											records using the form below.
										</p>
									</div>
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
											className="bg-primary hover:bg-primary/90 text-bold rounded px-4 py-2 text-xl font-bold text-white transition-colors hover:cursor-pointer disabled:cursor-not-allowed disabled:bg-gray-400"
										>
											{publishing ? 'Publishing...' : 'SAVE NEW RECORDS'}
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
