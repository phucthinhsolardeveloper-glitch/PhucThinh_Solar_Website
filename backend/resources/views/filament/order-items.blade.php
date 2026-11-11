@php($money = fn ($v) => number_format((float) $v, 0, ',', '.').'đ')
@if ($order)
    @php($discount = max(0.0, (float) $order->subtotal + (float) $order->shipping_fee - (float) $order->total))
    <div class="pts-order-items">
        <table>
            <thead><tr><th>Sản phẩm</th><th class="n">Đơn giá</th><th class="c">SL</th><th class="n">Thành tiền</th></tr></thead>
            <tbody>
                @foreach ($order->items as $item)
                    @php($price = (float) ($item->sale_price ?: $item->regular_price))
                    @php($option = trim($item->color.' '.$item->size))
                    <tr>
                        <td>
                            @if ($item->product_id)
                                <a href="{{ App\Filament\Resources\Products\ProductResource::getUrl('edit', ['record' => $item->product_id]) }}">{{ $item->name }}</a>
                            @else
                                {{ $item->name }}
                            @endif
                            @if ($option !== '')<span class="pts-attention__sub">{{ $option }}</span>@endif
                        </td>
                        <td class="n">{{ $money($price) }}</td>
                        <td class="c">{{ $item->quantity }}</td>
                        <td class="n"><strong>{{ $money($price * $item->quantity) }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <dl class="pts-order-items__totals">
            <dt>Tạm tính</dt><dd>{{ $money($order->subtotal) }}</dd>
            <dt>Phí giao hàng</dt><dd>{{ (float) $order->shipping_fee > 0 ? $money($order->shipping_fee) : 'Chờ xác nhận' }}</dd>
            @if ($discount > 0)<dt>Giảm giá</dt><dd>−{{ $money($discount) }}</dd>@endif
            <dt class="is-sum">Tổng cộng</dt><dd class="is-sum">{{ $money($order->total) }}</dd>
        </dl>
    </div>
@endif
