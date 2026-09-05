@if ($plan->prices->isEmpty())
    <p class="empty-state">金額はまだ登録されていません。</p>
@else
    <x-responsive-table caption="価格履歴" :cards="false">
        <thead>
            <tr>
                <th scope="col">適用開始日</th>
                <th scope="col">適用終了日</th>
                <th scope="col">金額</th>
                <th scope="col">登録者</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($plan->prices as $price)
                <tr>
                    <td>{{ $price->effective_from->format('Y/m/d') }}</td>
                    <td>{{ $price->effective_to?->format('Y/m/d') ?? '継続' }}</td>
                    <td>{{ $price->formatted_amount }}</td>
                    <td>{{ $price->relationLoaded('creator') ? ($price->creator?->display_name ?? '-') : '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </x-responsive-table>
@endif
