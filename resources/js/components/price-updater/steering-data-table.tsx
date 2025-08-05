import React from 'react';
import '../../../scss/price-updater/steering-data-table.scss';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';

export interface SteeringRecord {
	id: string;
	location_id: string;
	location_level: string;
	steer_type: string;
	steer_from: string;
	steer_to: string;
	length_of_rent_from: number;
	length_of_rent_to: number;
	vehicle_group: string;
	channel: string;
	direction: string;
	value: string;
	rate_plan: string;
	rate_code: string;
	available_type: string;
	point_of_sale_location: string;
	stable: boolean;
	identity: string;
	remark: string;
	created_at: string;
}

// Helper function to format date for display
const formatDate = (dateStr: string): string => {
	if (!dateStr) return '-';

	// Parse the date string more explicitly to avoid timezone issues
	const [datePart] = dateStr.split(' ');
	const [year, month, day] = datePart.split('-').map(Number);

	// Create date using local time to avoid timezone conversion issues
	const date = new Date(year, month - 1, day);

	return date.toLocaleDateString('el-GR', {
		day: '2-digit',
		month: '2-digit',
		year: 'numeric',
	});
};

// Helper function to format datetime for display
const formatDateTime = (dateStr: string): string => {
	if (!dateStr) return '-';

	// Parse the date string more explicitly to avoid timezone issues
	const [datePart, timePart] = dateStr.split(' ');
	const [year, month, day] = datePart.split('-').map(Number);
	const [hour, minute] = (timePart || '00:00').split(':').map(Number);

	// Create date using local time to avoid timezone conversion issues
	const date = new Date(year, month - 1, day, hour, minute);

	return date.toLocaleDateString('el-GR', {
		day: '2-digit',
		month: '2-digit',
		year: 'numeric',
		hour: '2-digit',
		minute: '2-digit',
	});
};

const SteeringDataTable: React.FC<{ steerings: SteeringRecord[]; id: string }> = ({
	steerings,
	id,
}) => {
	return (
		<div className="overflow-x-auto">
			<table
				id={id}
				className="steering-table min-w-full divide-y divide-gray-200 border border-gray-200"
			>
				<colgroup>
					<col className="table-col-1" />
					<col className="table-col-2" />
					<col className="table-col-3" />
					<col className="table-col-4" />
					<col className="table-col-5" />
					<col className="table-col-6" />
					<col className="table-col-7" />
					<col className="table-col-8" />
					<col className="table-col-9" />
					<col className="table-col-10" />
					<col className="table-col-11" />
					<col className="table-col-12" />
					<col className="table-col-13" />
					<col className="table-col-14" />
					<col className="table-col-15" />
				</colgroup>
				<thead className="bg-gray-50">
					<tr>
						<th
							scope="col"
							className="border-r border-gray-200 px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
						>
							ID
						</th>
						<th
							scope="col"
							className="border-r border-gray-200 px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
						>
							steering date
						</th>
						<th
							scope="col"
							className="border-r border-gray-200 px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
						>
							Location Level
						</th>
						<th
							scope="col"
							className="border-r border-gray-200 px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
						>
							Steer Type
						</th>
						<th
							scope="col"
							className="border-r border-gray-200 px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
						>
							Available Type
						</th>
						<th
							scope="col"
							className="border-r border-gray-200 px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
						>
							Group
						</th>
						<th
							scope="col"
							className="border-r border-gray-200 px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
						>
							Value
						</th>
						<th
							scope="col"
							className="border-r border-gray-200 px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
						>
							Lor (from)
						</th>
						<th
							scope="col"
							className="border-r border-gray-200 px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
						>
							Lor (to)
						</th>
						<th
							scope="col"
							className="border-r border-gray-200 px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
						>
							ssd
						</th>
						<th
							scope="col"
							className="border-r border-gray-200 px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
						>
							sst
						</th>
						<th
							scope="col"
							className="border-r border-gray-200 px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
						>
							sed
						</th>
						<th
							scope="col"
							className="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
						>
							set
						</th>
						<th
							scope="col"
							className="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
						>
							Remark
						</th>
					</tr>
				</thead>
				<tbody className="divide-y divide-gray-200 bg-white">
					{steerings.map((steering, index) => (
						<tr
							key={id + '_' + (steering.id || index)}
							className={index % 2 === 0 ? 'bg-white' : 'bg-gray-50'}
						>
							<td className="border-r border-gray-200 px-3 py-2 text-sm text-gray-900">
								{steering.id}
							</td>
							<td className="border-r border-gray-200 px-3 py-2 text-sm text-gray-900">
								{formatDate(steering.steer_from)}
							</td>
							<td className="border-r border-gray-200 px-3 py-2 text-sm text-gray-900">
								{steering.location_level}
							</td>
							<td className="border-r border-gray-200 px-3 py-2 text-sm text-gray-900">
								{steering.steer_type}
							</td>
							<td className="border-r border-gray-200 px-3 py-2 text-sm text-gray-900">
								{steering.available_type}
							</td>
							<td className="border-r border-gray-200 px-3 py-2 text-sm text-gray-900">
								{steering.vehicle_group}
							</td>
							<td className="border-r border-gray-200 px-3 py-2 text-center text-sm text-gray-900">
								{steering.value}
							</td>
							<td className="border-r border-gray-200 px-3 py-2 text-center text-sm text-gray-900">
								{steering.length_of_rent_from}
							</td>
							<td className="border-r border-gray-200 px-3 py-2 text-center text-sm text-gray-900">
								{steering.length_of_rent_to}
							</td>
							<td className="border-r border-gray-200 px-3 py-2 text-sm text-gray-900">
								{formatDateTime(steering.steer_from)}
							</td>
							<td className="border-r border-gray-200 px-3 py-2 text-sm text-gray-900">
								{formatDateTime(steering.steer_to)}
							</td>
							<td className="border-r border-gray-200 px-3 py-2 text-sm text-gray-900">
								{formatDateTime(steering.created_at)}
							</td>
							<td className="border-r border-gray-200 px-3 py-2 text-sm text-gray-900">
								{formatDateTime(steering.created_at)}
							</td>
							<td className="px-3 py-2 text-sm text-gray-900">
								{steering.remark ? (
									<Tooltip>
										<TooltipTrigger asChild>
											<span className="block cursor-help truncate">
												{steering.remark}
											</span>
										</TooltipTrigger>
										<TooltipContent>
											<p className="max-w-xs break-words">
												{steering.remark}
											</p>
										</TooltipContent>
									</Tooltip>
								) : (
									'-'
								)}
							</td>
						</tr>
					))}
				</tbody>
			</table>
		</div>
	);
};

export default SteeringDataTable;
