<?php
/** Pure DTO helpers for the read-only Nuxt About preview. */

function ptsAboutPublished($status)
{
    if(!is_scalar($status)) return false;
    return in_array('hienthi', array_filter(array_map('trim', explode(',', (string)$status))), true);
}

function ptsAboutPlainText($value)
{
    if(!is_scalar($value)) return '';
    $value = html_entity_decode((string)$value, ENT_QUOTES, 'UTF-8');
    $value = preg_replace('~<(?:br\s*/?|/p|/li)>~i', ' ', $value);
    return trim(preg_replace('/\s+/u', ' ', strip_tags($value)));
}

function ptsAboutSentences($value, $limit = 1)
{
    $plain = ptsAboutPlainText($value);
    if($plain === '') return '';
    $sentences = preg_split('/(?<=[.!?…])\s+/u', $plain, -1, PREG_SPLIT_NO_EMPTY);
    return trim(implode(' ', array_slice($sentences ?: array($plain), 0, max(1, (int)$limit))));
}

function ptsAboutImage($value, $root, $uploadPath, $publicPath)
{
    if(!is_scalar($value)) return '';
    $value = trim((string)$value);
    if($value === '' || basename($value) !== $value || !preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i', $value)) return '';
    return is_file($root.$uploadPath.$value) ? '/'.$publicPath.$value : '';
}

function ptsAboutNormalize($value, $func)
{
    $plain = ptsAboutPlainText($value);
    return $plain === '' ? '' : str_replace('-', ' ', $func->changeTitle($plain));
}

function ptsAboutFind(array $rows, array $needles, $func)
{
    foreach($rows as $row)
    {
        if(!is_array($row)) continue;
        $haystack = ptsAboutNormalize(isset($row['namevi']) ? $row['namevi'] : '', $func);
        foreach($needles as $needle) if(strpos($haystack, ptsAboutNormalize($needle, $func)) !== false) return $row;
    }
    return array();
}

function ptsAboutSafeLink($value)
{
    if(!is_scalar($value)) return null;
    $value = trim((string)$value);
    if($value === '#ptsContactModal') return null;
    if(!preg_match('~^(?:https?://[^\s]+|/?[a-zA-Z0-9][a-zA-Z0-9/_?&=.#%-]*)$~', $value)) return null;
    return $value;
}

function ptsAboutZaloUrl($phone)
{
    if(!is_scalar($phone)) return null;
    $phone = preg_replace('/[^0-9]/', '', trim((string)$phone));
    if($phone === '' || strlen($phone) > 20) return null;
    return 'https://zalo.me/'.$phone;
}

function ptsAboutLabels()
{
    return array(
        'storyTitle' => 'Giới thiệu Phúc Thịnh Solar',
        'storyEyebrow' => 'Hành trình phát triển',
        'timelineLabel' => 'Các mốc phát triển của Phúc Thịnh Solar',
        'peopleEyebrow' => 'Con người là nền tảng',
        'peopleTitle' => 'Nhân sự Phúc Thịnh Solar',
        'peopleFallback' => 'Đội ngũ giàu kinh nghiệm, tư vấn sát nhu cầu thực tế, triển khai có trách nhiệm và đồng hành lâu dài cùng khách hàng.',
        'principleExpertise' => 'Chuyên môn thực tế',
        'principleExpertiseDescription' => 'Đội ngũ kỹ sư, kỹ thuật viên giàu kinh nghiệm, am hiểu thực tế.',
        'principleSupport' => 'Đồng hành đến cùng',
        'principleSupportDescription' => 'Tư vấn trung thực, hỗ trợ xuyên suốt vòng đời dự án.',
        'principleCustomer' => 'Lấy khách hàng làm trung tâm',
        'principleCustomerDescription' => 'Luôn lắng nghe, thấu hiểu và mang đến giá trị thiết thực.',
        'trustTitle' => 'Vì sao khách hàng lựa chọn chúng tôi?',
        'trustDefault' => 'Một cam kết thực tế được duy trì xuyên suốt từ tư vấn, triển khai đến hỗ trợ sau bàn giao.',
        'trustAuthentic' => 'Thiết bị có nguồn gốc rõ ràng, thông tin minh bạch.',
        'trustTeam' => 'Tư vấn đúng nhu cầu và điều kiện triển khai thực tế.',
        'trustSupport' => 'Hỗ trợ kỹ thuật và chăm sóc khách hàng sau bàn giao.',
        'trustWarranty' => 'Điều kiện bảo hành rõ ràng, quy trình hỗ trợ minh bạch.',
        'compassTitle' => 'Tầm nhìn – Sứ mệnh – Giá trị cốt lõi',
        'certTitle' => 'Chứng nhận & năng lực',
        'certIntro' => 'Cam kết chất lượng được thể hiện bằng những chứng nhận đang được công bố.',
        'certLabel' => 'Chứng nhận',
        'certAll' => 'Xem tất cả chứng nhận',
        'certDetail' => 'Xem chi tiết',
        'contactTitle' => 'Cần tư vấn giải pháp năng lượng mặt trời?',
        'contactIntro' => 'Đội ngũ Phúc Thịnh Solar luôn sẵn sàng đồng hành cùng bạn.',
        'contactPrimary' => 'Nhận tư vấn ngay'
    );
}

function ptsAboutPayload(array $sections, array $labels, $hotline = '', $zalo = '')
{
    $hotline = is_scalar($hotline) ? trim((string)$hotline) : '';
    $hotlineDto = $hotline === '' ? null : array('label' => 'Gọi ngay hotline', 'text' => $hotline, 'tel' => preg_replace('/[^0-9+]/', '', $hotline));
    return array(
        'schemaVersion' => 2,
        'locale' => 'vi',
        'path' => '/gioi-thieu/',
        'title' => 'Giới thiệu Phúc Thịnh Solar',
        'story' => $sections['story'],
        'history' => $sections['history'],
        'people' => $sections['people'],
        'trust' => $sections['trust'],
        'compass' => $sections['compass'],
        'certificates' => $sections['certificates'],
        'cta' => array(
            'title' => $sections['cta']['title'],
            'description' => $sections['cta']['description'],
            'image' => $sections['cta']['image'],
            'href' => $sections['cta']['href'],
            'buttonLabel' => $labels['contactPrimary'],
            'hotline' => $hotlineDto,
            'zaloUrl' => ptsAboutZaloUrl($zalo)
        ),
        'labels' => array(
            'story' => 'Về Phúc Thịnh Solar',
            'history' => 'Lịch sử phát triển',
            'people' => 'Con người',
            'compass' => 'Tầm nhìn và sứ mệnh',
            'certificates' => 'Năng lực và chứng nhận',
            'timeline' => $labels['timelineLabel'],
            'certLabel' => $labels['certLabel'],
            'certAll' => $labels['certAll'],
            'certDetail' => $labels['certDetail']
        )
    );
}
