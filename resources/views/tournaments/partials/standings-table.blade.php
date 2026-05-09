<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-blue-950/10 text-sm">
        <thead class="bg-[#071a80] text-white">
            <tr>
                <th class="px-3 py-3 text-left text-xs font-black uppercase">Rank</th>
                <th class="px-3 py-3 text-left text-xs font-black uppercase">Entrant</th>
                <th class="px-3 py-3 text-right text-xs font-black uppercase">P</th>
                <th class="px-3 py-3 text-right text-xs font-black uppercase">W</th>
                <th class="px-3 py-3 text-right text-xs font-black uppercase">L</th>
                <th class="px-3 py-3 text-right text-xs font-black uppercase">GD</th>
                <th class="px-3 py-3 text-right text-xs font-black uppercase">PD</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-blue-950/10 bg-white">
            @foreach ($standings as $row)
                <tr>
                    <td class="px-3 py-3 font-black text-[#071a80]">{{ $row['rank'] }}</td>
                    <td class="px-3 py-3 font-bold text-blue-950/75">{{ $row['entrant']->displayName() }}</td>
                    <td class="px-3 py-3 text-right font-bold">{{ $row['played'] }}</td>
                    <td class="px-3 py-3 text-right font-bold">{{ $row['won'] }}</td>
                    <td class="px-3 py-3 text-right font-bold">{{ $row['lost'] }}</td>
                    <td class="px-3 py-3 text-right font-bold">{{ $row['game_diff'] }}</td>
                    <td class="px-3 py-3 text-right font-bold">{{ $row['point_diff'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
