export interface DataItem {
	name: string;
	start: string;
	end: string;
	yield: string;
	yield_s: string;
	price?: string;
	Lor?: number;
	Mlor?: number;
	lor?: number;
	mlor?: number;
	peaks: Array<{
		start: string;
		end: string;
		yield: string;
		yield_s: string;
		price?: string;
		Lor?: number;
		Mlor?: number;
		lor?: number;
		mlor?: number;
	}>;
}

export interface ParsedData {
	date: string;
	location: string;
	data: DataItem[];
}

export interface PriceData {
	yielding_date: string;
	car_group: string;
	type: string;
	start_date: string;
	end_date: string;
	yield: string;
	yield_code: string;
	price: string;
	pool: string;
	steer_from?: string;
	steer_to?: string;
	steer_type?: string;
	value?: string;
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
	price_data_stored?: {
		stored_count: number;
		total_count: number;
		errors: string[];
	};
}

export interface PublishRecord {
	id?: string;
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
			(document.querySelector('meta[name="app-csrf-token"]') as HTMLMetaElement)?.content ||
			''
		);
	}

	/**
	 * Make API request with session establishment and retry logic
	 */
	private static async makeApiRequest(
		url: string,
		options: RequestInit,
		retryCount = 0
	): Promise<Response> {
		try {
			// Get token from meta tag
			const csrfToken = this.getCsrfToken();

			// Update headers with CSRF token
			const updatedOptions: RequestInit = {
				...options,
				credentials: 'same-origin' as RequestCredentials,
				headers: {
					...options.headers,
					'X-CSRF-TOKEN': csrfToken,
					Accept: 'application/json',
				},
			};

			const response = await fetch(url, updatedOptions);

			// If we get a 419 error and haven't retried yet, try again
			if (response.status === 419 && retryCount === 0) {
				console.log('CSRF token mismatch, retrying after session refresh...');

				// Try to refresh the session and get a new token
				await new Promise((resolve) => setTimeout(resolve, 200));

				return this.makeApiRequest(url, options, retryCount + 1);
			}

			return response;
		} catch (error) {
			console.error('API request failed:', error);
			throw error;
		}
	}

	/**
	 * Parse date from DD/MM/YYYY format to YYYY-MM-DD
	 */
	private static parseDate(dateStr: string): string {
		if (!dateStr) {
			throw new Error('Date string is required');
		}

		const parts = dateStr.split('/');
		if (parts.length !== 3) {
			throw new Error(`Invalid date format: ${dateStr}. Expected DD/MM/YYYY format.`);
		}

		const [day, month, year] = parts;
		return `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
	}

	/**
	 * Parse yield_s format and return value_type and value
	 */
	private static parseYieldS(yieldS: string): { valueType: string; value: number } {
		if (!yieldS) {
			throw new Error('Yield_s string is required');
		}

		// Check if it's in PXY format
		if (yieldS.startsWith('P')) {
			const numericPart = yieldS.substring(1);
			const numericValue = parseInt(numericPart, 10);
			if (isNaN(numericValue)) {
				throw new Error(`Invalid yield_s format: ${yieldS}. Expected PXY format.`);
			}
			return { valueType: 'VALUE_TYPE_RATE_P', value: numericValue };
		}

		// Check if it's in IXY format
		if (yieldS.startsWith('I')) {
			const numericPart = yieldS.substring(1);
			const numericValue = parseInt(numericPart, 10);
			if (isNaN(numericValue)) {
				throw new Error(`Invalid yield_s format: ${yieldS}. Expected IXY format.`);
			}
			return { valueType: 'VALUE_TYPE_RATE_I', value: numericValue };
		}

		throw new Error(`Invalid yield_s format: ${yieldS}. Expected PXY or IXY format.`);
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
	 * Convert entry data to publish format (excluding price data)
	 */
	static convertToPublishFormat(parsedData: ParsedData): PublishRecord[] {
		const records: PublishRecord[] = [];

		parsedData.data.forEach((item) => {
			// Parse yield_s for main period
			const mainYieldS = this.parseYieldS(item.yield_s);

			// Get length of rent values from JSON or use defaults (case insensitive)
			const lengthOfRentFrom = item.Lor ?? item.lor ?? 1;
			const lengthOfRentTo = item.Mlor ?? item.mlor ?? 999;

			// Main period
			records.push({
				location_level: 'LOCATION_LEVEL_BRANCH',
				location_id: parsedData.location,
				steer_type: 'STEER_TYPE_UDA',
				length_of_rent_from: lengthOfRentFrom,
				length_of_rent_to: lengthOfRentTo,
				vehicle_type: 'VEHICLE_TYPE_P',
				vehicle_group: item.name,
				yield_type: 'TYPE_LEVEL_PLAIN',
				value_type: mainYieldS.valueType,
				value: mainYieldS.value,
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
				// Parse yield_s for peak
				const peakYieldS = this.parseYieldS(peak.yield_s);

				// Get length of rent values from peak JSON or use defaults (case insensitive)
				const peakLengthOfRentFrom = peak.Lor ?? peak.lor ?? 1;
				const peakLengthOfRentTo = peak.Mlor ?? peak.mlor ?? 999;

				records.push({
					location_level: 'LOCATION_LEVEL_BRANCH',
					location_id: parsedData.location,
					steer_type: 'STEER_TYPE_PEAK',
					length_of_rent_from: peakLengthOfRentFrom,
					length_of_rent_to: peakLengthOfRentTo,
					vehicle_type: 'VEHICLE_TYPE_P',
					vehicle_group: item.name,
					yield_type: 'TYPE_LEVEL_PLAIN',
					value_type: peakYieldS.valueType,
					value: peakYieldS.value,
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
	 * Extract price data from entry data
	 */
	static extractPriceData(parsedData: ParsedData): PriceData[] {
		const priceData: PriceData[] = [];

		parsedData.data.forEach((item) => {
			// Always create UDA record for main period if price exists

			priceData.push({
				yielding_date: parsedData.date,
				car_group: item.name,
				type: 'UDA',
				start_date: this.parseDate(item.start),
				end_date: this.parseDate(item.end),
				yield: item.yield,
				yield_code: item.yield_s,
				price: item.price || '',
				pool: parsedData.location,
			});

			// Create PEAK records only if peaks array is not empty
			if (item.peaks && item.peaks.length > 0) {
				item.peaks.forEach((peak) => {
					priceData.push({
						yielding_date: parsedData.date,
						car_group: item.name,
						type: 'PEAK',
						start_date: this.parseDate(peak.start),
						end_date: this.parseDate(peak.end),
						yield: peak.yield,
						yield_code: peak.yield_s,
						price: peak.price || '',
						pool: parsedData.location,
					});
				});
			}
		});

		return priceData;
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

			const response = await this.makeApiRequest('/price-updater/fetch-prices', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
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
	 * Publish steering records to the API and store price data in database
	 */
	static async publishPrices(entryData: string): Promise<{
		result: PublishResponse | null;
		error: string | null;
		processedRowsCount: number | null;
		priceDataStored?: {
			stored_count: number;
			total_count: number;
			errors: string[];
		};
	}> {
		try {
			const parsedData: ParsedData = JSON.parse(entryData) as ParsedData;
			const publishRecords = {
				steerings: this.convertToPublishFormat(parsedData),
				price_data: this.extractPriceData(parsedData),
			};

			const response = await this.makeApiRequest('/price-updater/publish-prices', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
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
				priceDataStored: result.price_data_stored,
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
	 * Delete steering records from the API
	 */
	static async deletePrices(steeringRecords: SteeringRecord[]): Promise<{
		result: PublishResponse | null;
		error: string | null;
		processedRowsCount: number | null;
	}> {
		try {
			const deleteRecords = {
				steerings: this.convertToDeleteFormat(steeringRecords),
			};

			const response = await this.makeApiRequest('/price-updater/delete-prices', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
				},
				body: JSON.stringify({ data: JSON.stringify(deleteRecords) }),
			});

			const result: PublishResponse = (await response.json()) as PublishResponse;

			if (!response.ok) {
				return {
					result: null,
					error: result.error || 'Failed to delete prices',
					processedRowsCount: null,
				};
			}

			return {
				result,
				error: null,
				processedRowsCount: deleteRecords.steerings.length,
			};
		} catch (deleteError) {
			console.error('Delete error:', deleteError);
			return {
				result: null,
				error: 'Failed to delete steering records',
				processedRowsCount: null,
			};
		}
	}

	/**
	 * Convert steering records to delete format
	 */
	private static convertToDeleteFormat(steeringRecords: SteeringRecord[]): PublishRecord[] {
		return steeringRecords.map((record) => ({
			id: record.id,
			location_level: record.location_level,
			location_id: record.location_id,
			steer_type: record.steer_type,
			length_of_rent_from: record.length_of_rent_from,
			length_of_rent_to: record.length_of_rent_to,
			vehicle_type: 'VEHICLE_TYPE_P',
			vehicle_group: record.vehicle_group,
			yield_type: 'TYPE_LEVEL_PLAIN',
			value_type: 'VALUE_TYPE_RATE_P',
			value: parseFloat(record.value) || 0,
			steer_from: record.steer_from,
			steer_to: record.steer_to,
			identity: record.identity,
			channel: record.channel,
			available_type: record.available_type,
			remark: record.remark,
			operation: 'DELETE_WITH_STEER_PERIOD_SPLIT',
		}));
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
			// Parse yield_s for main period
			const mainYieldS = this.parseYieldS(item.yield_s);

			// Get length of rent values from JSON or use defaults (case insensitive)
			const lengthOfRentFrom = item.Lor ?? item.lor ?? 1;
			const lengthOfRentTo = item.Mlor ?? item.mlor ?? 999;

			// Main period
			records.push({
				id: '',
				steer_type: 'STEER_TYPE_UDA',
				steer_from: this.parseDate(item.start) + ' 00:00',
				steer_to: this.parseDate(item.end) + ' 23:59',
				length_of_rent_from: lengthOfRentFrom,
				length_of_rent_to: lengthOfRentTo,
				vehicle_group: item.name,
				value: mainYieldS.value.toString(),
				...baseFields,
			});

			// Peaks
			item.peaks.forEach((peak) => {
				// Parse yield_s for peak
				const peakYieldS = this.parseYieldS(peak.yield_s);

				// Get length of rent values from peak JSON or use defaults (case insensitive)
				const peakLengthOfRentFrom = peak.Lor ?? peak.lor ?? 1;
				const peakLengthOfRentTo = peak.Mlor ?? peak.mlor ?? 999;

				records.push({
					id: '',
					steer_type: 'STEER_TYPE_PEAK',
					steer_from: this.parseDate(peak.start) + ' 00:00',
					steer_to: this.parseDate(peak.end) + ' 23:59',
					length_of_rent_from: peakLengthOfRentFrom,
					length_of_rent_to: peakLengthOfRentTo,
					vehicle_group: item.name,
					value: peakYieldS.value.toString(),
					...baseFields,
				});
			});
		});

		return records;
	}
}
