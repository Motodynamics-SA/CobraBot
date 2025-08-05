import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import { AppHeader } from '@/components/app-header';
import { Button } from '@/components/ui/button';

const DataEntryPage: React.FC = () => {
	// const defaultJson = `{ "date": "11/03/2025", "location": "664", "data": [ { "name": "MDMR", "start": "01/06/2025", "end": "01/09/2025", "yield": "49", "yield_s": "P51", "price": "205,4703", "Lor": 0, "Mlor": 999, "peaks": [ { "start": "06/06/2025", "end": "30/06/2025", "yield": "55", "yield_s": "P45", "price": "275,3507", "Lor": 0, "Mlor": 999 } ] }, { "name": "EDMR", "start": "01/06/2025", "end": "30/06/2025", "yield": "55", "yield_s": "P45", "price": "330,4209", "Lor": 0, "Mlor": 999, "peaks": [] }, { "name": "CDMR", "start": "01/06/2025", "end": "30/06/2025", "yield": "49", "yield_s": "P51", "price": "328,7525", "Lor": 0, "Mlor": 999, "peaks": [] }, { "name": "CFMR", "start": "01/06/2025", "end": "30/06/2025", "yield": "49", "yield_s": "P51", "price": "361,622", "Lor": 0, "Mlor": 999, "peaks": [ { "start": "01/06/2025", "end": "13/06/2025", "yield": "52", "yield_s": "P48", "price": "418,6137", "Lor": 0, "Mlor": 999 }, { "start": "27/06/2025", "end": "30/06/2025", "yield": "52", "yield_s": "P48", "price": "418,6137", "Lor": 0, "Mlor": 999 } ] }, { "name": "MDAR", "start": "01/06/2025", "end": "30/06/2025", "yield": "55", "yield_s": "P45", "price": "330,4209", "Lor": 0, "Mlor": 999, "peaks": [] }, { "name": "EDAR", "start": "01/06/2025", "end": "30/06/2025", "yield": "53", "yield_s": "P47", "price": "344,6504", "Lor": 0, "Mlor": 999, "peaks": [] }, { "name": "CDAR", "start": "01/06/2025", "end": "30/06/2025", "yield": "49", "yield_s": "P51", "price": "378,071", "Lor": 0, "Mlor": 999, "peaks": [] }, { "name": "CFAR", "start": "01/06/2025", "end": "30/06/2025", "yield": "48", "yield_s": "P52", "price": "396,0796", "Lor": 0, "Mlor": 999, "peaks": [ { "start": "01/06/2025", "end": "13/06/2025", "yield": "51", "yield_s": "P49", "price": "458,5025", "Lor": 0, "Mlor": 999 }, { "start": "27/06/2025", "end": "30/06/2025", "yield": "51", "yield_s": "P49", "price": "458,5025", "Lor": 0, "Mlor": 999 } ] }, { "name": "CXAR", "start": "01/06/2025", "end": "30/06/2025", "yield": "49", "yield_s": "P51", "price": "434,7774", "Lor": 0, "Mlor": 999, "peaks": [ { "start": "01/06/2025", "end": "13/06/2025", "yield": "52", "yield_s": "P48", "price": "503,2983", "Lor": 0, "Mlor": 999 }, { "start": "27/06/2025", "end": "30/06/2025", "yield": "52", "yield_s": "P48", "price": "503,2983", "Lor": 0, "Mlor": 999 } ] }, { "name": "IFAR", "start": "01/06/2025", "end": "30/06/2025", "yield": "48", "yield_s": "P52", "price": "491,9663", "Lor": 0, "Mlor": 999, "peaks": [ { "start": "01/06/2025", "end": "13/06/2025", "yield": "52", "yield_s": "P48", "price": "597,9632", "Lor": 0, "Mlor": 999 }, { "start": "27/06/2025", "end": "30/06/2025", "yield": "52", "yield_s": "P48", "price": "597,9632", "Lor": 0, "Mlor": 999 } ] } ] }`;

	const [value, setValue] = useState('');
	const [error, setError] = useState<string | null>(null);
	const [submitting, setSubmitting] = useState(false);

	const handleNext = (e: React.FormEvent) => {
		e.preventDefault();
		setError(null);
		try {
			validateInputData(JSON.parse(value) as Record<string, unknown>);
		} catch (err: unknown) {
			setError(err instanceof Error ? err.message : String(err));
			return;
		}
		setSubmitting(true);
		router.post(
			'/price-updater/data-entry',
			{ data: value },
			{
				onFinish: () => setSubmitting(false),
			}
		);
	};

	const validateInputData = (data: Record<string, unknown>) => {
		if (!data || typeof data !== 'object') throw new Error('Input must be a JSON object!');
		if (!data.location) throw new Error('Location is not specified!');
		if (!data.date) throw new Error('Steering date is not specified!');
		if (isNaN(Number(data.location)))
			throw new Error('Location should be an integer area code. For example 42484.');
		if (!isValidDate(data.date as string))
			throw new Error(
				'Date ' + (data.date as string) + ' is not valid. The format should be: dd/mm/yyyy'
			);
		if (!Array.isArray(data.data)) throw new Error('The data field must be an array.');
		for (let i = 0; i < data.data.length; i++) {
			const group = data.data[i] as {
				name: string;
				start: string;
				end: string;
				yield_s: number;
				peaks: { start: string; end: string; yield_s: number }[];
			};
			if (!group.start)
				throw new Error('Start date for group: ' + group.name + ' is not specified!');
			if (!isValidDate(group.start))
				throw new Error(
					'Date ' + group.start + ' is not valid. The format should be: dd/mm/yyyy'
				);
			if (!group.end)
				throw new Error('End date for group: ' + group.name + ' is not specified!');
			if (!isValidDate(group.end))
				throw new Error(
					'Date ' + group.end + ' is not valid. The format should be: dd/mm/yyyy'
				);
			if (!group.yield_s)
				throw new Error('Yield value for group: ' + group.name + ' is not specified!');
			if (!Array.isArray(group.peaks))
				throw new Error('Peaks for group: ' + group.name + ' must be an array!');
			for (let peakIndex = 0; peakIndex < group.peaks.length; peakIndex++) {
				const peak = group.peaks[peakIndex];
				if (!peak.start)
					throw new Error(
						'Start date for peak ' +
							(peakIndex + 1) +
							' for group: ' +
							group.name +
							' is not specified!'
					);
				if (!isValidDate(peak.start))
					throw new Error(
						'Date ' + peak.start + ' is not valid. The format should be: dd/mm/yyyy'
					);
				if (!peak.end)
					throw new Error(
						'End date for peak ' +
							(peakIndex + 1) +
							' for group: ' +
							group.name +
							' is not specified!'
					);
				if (!isValidDate(peak.end))
					throw new Error(
						'Date ' + peak.end + ' is not valid. The format should be: dd/mm/yyyy'
					);
				if (!peak.yield_s)
					throw new Error(
						'Yield value for peak ' +
							(peakIndex + 1) +
							' for group: ' +
							group.name +
							' is not specified!'
					);
			}
		}
	};

	// Helper for dd/mm/yyyy
	function isValidDate(dateStr: string): boolean {
		if (typeof dateStr !== 'string') return false;
		const match = dateStr.match(/^([0-2][0-9]|3[01])\/(0[1-9]|1[0-2])\/\d{4}$/);
		if (!match) return false;
		const [day, month, year] = dateStr.split('/').map(Number);
		const date = new Date(year, month - 1, day);
		return (
			date.getFullYear() === year && date.getMonth() === month - 1 && date.getDate() === day
		);
	}

	return (
		<div className="min-h-screen bg-gray-50">
			<AppHeader />
			<form
				onSubmit={handleNext}
				className="max-w-3/4 sm:max-w-9/10 md:max-w-3/4 lg:max-w-3/4 mx-auto mt-10 w-full rounded bg-white p-6 shadow"
			>
				<div>
					<h1 className="mb-6 text-2xl font-bold">JSON Data Entry</h1>

					<textarea
						className="mb-4 h-64 w-full rounded border p-4 font-mono text-base"
						value={value}
						onChange={(e) => setValue(e.target.value)}
						placeholder="Paste your JSON here..."
						disabled={submitting}
					/>
					{error && <div className="mb-5 text-red-600">{error}</div>}
				</div>
				<div className="max-w-3xs flex justify-start">
					<Button
						size="xl"
						type="submit"
						className="bg-primary hover:bg-primary/90 mt-4 w-full rounded px-6 py-4 text-white disabled:opacity-50"
						disabled={submitting}
					>
						{submitting ? 'Loading...' : 'Next'}
					</Button>
				</div>
			</form>
		</div>
	);
};

export default DataEntryPage;
