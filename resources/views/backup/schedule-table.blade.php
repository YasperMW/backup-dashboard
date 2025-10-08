<table class="min-w-full divide-y divide-gray-200 mb-2">
    <thead class="bg-gray-50">
        <tr>
            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Frequency</th>
            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Days</th>
            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Source(s)</th>
            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Destination</th>
            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Enabled</th>
        </tr>
    </thead>
    <tbody class="bg-white divide-y divide-gray-200">
        @foreach($schedules as $schedule)
            <tr>
                <td class="px-4 py-2 text-sm text-gray-900">{{ ucfirst($schedule->frequency) }}</td>
                <td class="px-4 py-2 text-sm text-gray-900">{{ $schedule->time }}</td>
                <td class="px-4 py-2 text-sm text-gray-900">{{ $schedule->days_of_week ?? '-' }}</td>
                <td class="px-4 py-2 text-sm text-gray-900">{{ is_array($schedule->source_directories) ? implode(', ', $schedule->source_directories) : $schedule->source_directories }}</td>
                <td class="px-4 py-2 text-sm text-gray-900">{{ $schedule->destination_directory }}</td>
                <td class="px-4 py-2 text-sm text-gray-900">{{ $schedule->enabled ? 'Yes' : 'No' }}</td>
            </tr>
        @endforeach
    </tbody>
</table> 