{{-- Bottom of every transactional email: how to reach us, who we are, and that nobody typed this by hand. --}}
<tr>
    <td align="center" style="padding:0 12px 28px" valign="top">
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="640" style="width:100%;max-width:640px;background:#09285e;border-radius:0 0 14px 14px">
            <tr>
                <td style="padding:22px 28px 18px;color:#ffffff;font-size:13px;line-height:22px">
                    <strong style="display:block;margin-bottom:8px;color:#f1c75b;font-size:15px">Phúc Thịnh Solar luôn sẵn sàng hỗ trợ</strong>
                    @if ($brand['hotline'] !== '')
                        Tư vấn bán hàng: <a href="tel:{{ preg_replace('/[^+\d]/', '', $brand['hotline']) }}" style="color:#ffffff;font-weight:bold;text-decoration:none">{{ $brand['hotline'] }}</a><br>
                    @endif
                    @if ($brand['technicalHotline'] !== '')
                        Hỗ trợ kỹ thuật: <a href="tel:{{ preg_replace('/[^+\d]/', '', $brand['technicalHotline']) }}" style="color:#ffffff;font-weight:bold;text-decoration:none">{{ $brand['technicalHotline'] }}</a><br>
                    @endif
                    Thời gian làm việc: {{ $brand['workingHours'] }}<br>
                    Website: <a href="{{ $brand['home'] }}" style="color:#f1c75b;text-decoration:none" target="_blank">{{ preg_replace('~^https?://~', '', rtrim($brand['home'], '/')) }}</a>
                </td>
            </tr>
            @if ($brand['social'] !== [])
                <tr>
                    <td style="padding:0 28px 18px;color:#b8c7e4;font-size:12px;line-height:20px">
                        @foreach ($brand['social'] as $index => $account)
                            @if ($index > 0)<span style="color:#29467b"> · </span>@endif<a href="{{ $account['href'] }}" style="color:#dbe7ff;text-decoration:none" target="_blank">{{ $account['label'] }}</a>
                        @endforeach
                    </td>
                </tr>
            @endif
            <tr>
                <td style="padding:16px 28px 22px;border-top:1px solid #29467b;color:#b8c7e4;font-size:11px;line-height:18px">
                    <strong style="color:#ffffff">{{ $brand['name'] }}</strong><br>
                    @if ($brand['address'] !== ''){{ $brand['address'] }}<br>@endif
                    Email này được gửi tự động từ website. Vui lòng không chia sẻ thông tin đơn hàng hoặc tài khoản cho người không liên quan.
                </td>
            </tr>
        </table>
    </td>
</tr>
