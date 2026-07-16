<div class="section-title">{{ $sectionTitle }}</div>

@forelse ($groups as $group)
    <div class="po-title">{{ $group['order_number'] }}</div>
    <table class="po-meta">
        <tr>
            <td style="width: 14%; font-weight: bold;">Po Date :</td>
            <td style="width: 36%">{{ $group['order_date_display'] }}</td>
            <td style="width: 14%; font-weight: bold;">Po Aprove Date :</td>
            <td style="width: 36%">{{ $group['approved_date_time_display'] }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Po Create :</td>
            <td>{{ $group['created_date_time_display'] }}</td>
            <td></td>
            <td></td>
        </tr>
    </table>

    <table class="data-table detail-table">
        <thead>
            <tr>
                <th style="width: 4%">No</th>
                <th style="width: 28%">Item Name</th>
                <th style="width: 7%">Qty PO</th>
                <th style="width: 16%">PR Number</th>
                <th style="width: 18%">PI Number</th>
                <th style="width: 7%">Qty PI</th>
                <th style="width: 7%">PO-PI<br>(Day)</th>
                <th style="width: 7%">PR-PI<br>(Day)</th>
                <th style="width: 6%">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($group['items'] as $row)
                <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td class="center">{{ $row['no'] }}</td>
                    <td>{{ $row['item_name'] }}</td>
                    <td class="number">{{ $row['qty_po'] }}</td>
                    <td class="center nowrap">{{ $row['pr_number'] }}</td>
                    <td class="center nowrap">{{ $row['pi_number'] }}</td>
                    <td class="number">{{ $row['qty_pi'] }}</td>
                    <td class="center">{{ $row['po_pi'] }}</td>
                    <td class="center">{{ $row['pr_pi'] }}</td>
                    <td class="center">{{ $row['status'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@empty
    @if ($showEmptyRow)
        <table class="po-meta">
            <tr>
                <td style="width: 14%; font-weight: bold;">Po Date :</td>
                <td style="width: 36%"></td>
                <td style="width: 14%; font-weight: bold;">Po Aprove Date :</td>
                <td style="width: 36%"></td>
            </tr>
            <tr>
                <td style="font-weight: bold;">Po Create :</td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        </table>

        <table class="data-table detail-table">
            <thead>
                <tr>
                    <th style="width: 4%">No</th>
                    <th style="width: 28%">Item Name</th>
                    <th style="width: 7%">Qty PO</th>
                    <th style="width: 16%">PR Number</th>
                    <th style="width: 18%">PI Number</th>
                    <th style="width: 7%">Qty PI</th>
                    <th style="width: 7%">PO-PI<br>(Day)</th>
                    <th style="width: 7%">PR-PI<br>(Day)</th>
                    <th style="width: 6%">Status</th>
                </tr>
            </thead>
            <tbody>
                <tr class="empty-row">
                    <td class="center">0</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="center">0</td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    @endif
@endforelse
