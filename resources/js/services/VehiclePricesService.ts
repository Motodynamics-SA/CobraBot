export interface DataItem {
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

export interface ParsedData {
	date: string;
	location: string;
	data: DataItem[];
}

export interface SteeringRecord {
	id: string;
	steer_type: string;
	steer_from: string;
	steer_to: string;
	length_of_rent_from: number;
	length_of_rent_to: number;
	vehicle_group: string;
	value: string;
	location_id: string;
	location_level: string;
	channel: string;
	direction: string;
	rate_plan: string;
	rate_code: string;
	available_type: string;
	point_of_sale_location: string;
	stable: boolean;
	identity: string;
	remark: string;
	created_at: string;
}

export interface ApiResponse {
	prices?: {
		steerings?: SteeringRecord[];
		[key: string]: unknown;
	};
	error?: string;
}

export interface PublishResponse {
	message?: string;
	response?: unknown;
	error?: string;
}

export interface PublishRecord {
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

export interface ApiPayload {
	location_id: string;
	location_level: string;
	steer_from: string;
	steer_to: string;
	limit: number;
	offset: number;
}

export class VehiclePricesService {
	private static getCsrfToken(): string {
		return (
			(document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
		);
	}

	/**
	 * Parse date from DD/MM/YYYY format to YYYY-MM-DD
	 */
	private static parseDate(dateStr: string): string {
		const [day, month, year] = dateStr.split('/');
		return `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
	}

	/**
	 * Analyze data and extract location and date range for API payload
	 */
	static analyzeData(data: ParsedData): ApiPayload {
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
		const parsedDates = allDates.map((date) => new Date(this.parseDate(date)));
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
	}

	/**
	 * Convert entry data to publish format
	 */
	static convertToPublishFormat(parsedData: ParsedData): PublishRecord[] {
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
				steer_from: this.parseDate(item.start) + ' 00:00',
				steer_to: this.parseDate(item.end) + ' 23:59',
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
					steer_from: this.parseDate(peak.start) + ' 00:00',
					steer_to: this.parseDate(peak.end) + ' 23:59',
					identity: 'franchise',
					channel: 'GIVO',
					available_type: 'AVAILABLE_TYPE_CONDITIONAL',
					remark: `Peak ${item.name}`,
					operation: 'UPSERT_WITH_STEER_PERIOD_SPLIT',
				});
			});
		});

		return records;
	}

	/**
	 * Fetch prices data from the API
	 */
	static async fetchPrices(
		entryData: string
	): Promise<{ prices: Record<string, unknown> | null; error: string | null }> {
		try {
			// Parse the entry data
			const parsedData: ParsedData = JSON.parse(entryData) as ParsedData;

			// Analyze the data to extract location and date range
			const apiPayload = this.analyzeData(parsedData);

			const response = await fetch('/price-updater/fetch-prices', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-CSRF-TOKEN': this.getCsrfToken(),
				},
				body: JSON.stringify({ data: JSON.stringify(apiPayload) }),
			});

			const result: ApiResponse = (await response.json()) as ApiResponse;

			if (!response.ok) {
				return { prices: null, error: result.error || 'Failed to fetch prices' };
			}

			return { prices: result.prices || null, error: null };
		} catch (parseError) {
			console.error('Parse error:', parseError);
			return { prices: null, error: 'Failed to parse entry data' };
		}
	}

	/**
	 * Publish steering records to the API
	 */
	static async publishPrices(
		entryData: string
	): Promise<{
		result: PublishResponse | null;
		error: string | null;
		processedRowsCount: number | null;
	}> {
		try {
			const parsedData: ParsedData = JSON.parse(entryData) as ParsedData;
			const publishRecords = {
				steerings: this.convertToPublishFormat(parsedData),
			};

			console.log('publishRecords', JSON.stringify(publishRecords, null, 2));

			const response = await fetch('/price-updater/publish-prices', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-CSRF-TOKEN': this.getCsrfToken(),
				},
				body: JSON.stringify({ data: JSON.stringify(publishRecords) }),
			});

			const result: PublishResponse = (await response.json()) as PublishResponse;

			if (!response.ok) {
				return {
					result: null,
					error: result.error || 'Failed to publish prices',
					processedRowsCount: null,
				};
			}

			return {
				result,
				error: null,
				processedRowsCount: publishRecords.steerings.length,
			};
		} catch (publishError) {
			console.error('Publish error:', publishError);
			return {
				result: null,
				error: 'Failed to publish steering records',
				processedRowsCount: null,
			};
		}
	}

	/**
	 * Parse entry data and convert to steering records format
	 */
	static parseEntryDataToSteeringRecords(entryData: string): {
		records: SteeringRecord[];
		error: string | null;
	} {
		try {
			const parsedData: ParsedData = JSON.parse(entryData) as ParsedData;
			const records = this.convertEntryDataToSteeringRecords(parsedData);
			return { records, error: null };
		} catch (parseError) {
			console.error('Failed to parse entry data', parseError);
			return { records: [], error: 'Failed to parse entry data' };
		}
	}

	/**
	 * Convert parsed data to steering records format for display
	 */
	private static convertEntryDataToSteeringRecords(parsedData: ParsedData): SteeringRecord[] {
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
				steer_from: this.parseDate(item.start) + ' 00:00',
				steer_to: this.parseDate(item.end) + ' 23:59',
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
					steer_from: this.parseDate(peak.start) + ' 00:00',
					steer_to: this.parseDate(peak.end) + ' 23:59',
					length_of_rent_from: 1,
					length_of_rent_to: 1,
					vehicle_group: item.name,
					value: peak.yield || '-',
					...baseFields,
				});
			});
		});

		return records;
	}
}
