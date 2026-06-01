<?php
// 设置API路径
define('API_URI', api_uri());
// 设置仅 Https
define('Only_Https', true);
// 设置歌单文件缓存及时间
define('CACHE', false);
define('CACHE_TIME', 86400);
// 设置AUTH密钥-更改'meting-secret'
define('AUTH', false);
define('AUTH_SECRET', 'meting-secret');

if (!isset($_GET['type']) || !isset($_GET['id'])) {
    include __DIR__ . '/public/index.php';
    exit;
}

$server = filter_input(INPUT_GET, 'server', FILTER_SANITIZE_SPECIAL_CHARS) ?: 'netease';
$type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_SPECIAL_CHARS);
$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_SPECIAL_CHARS);
$br = filter_input(INPUT_GET, 'br', FILTER_SANITIZE_SPECIAL_CHARS) ?: '2147483';
$dwrc = filter_input(INPUT_GET, 'dwrc', FILTER_SANITIZE_SPECIAL_CHARS) ?: 'false';
$yrc = filter_input(INPUT_GET, 'yrc', FILTER_SANITIZE_SPECIAL_CHARS) ?: 'false';
$qrc = filter_input(INPUT_GET, 'qrc', FILTER_SANITIZE_SPECIAL_CHARS) ?: 'false';
$trlrc = filter_input(INPUT_GET, 'trlrc', FILTER_SANITIZE_SPECIAL_CHARS) ?: 'false';
$picsize = filter_input(INPUT_GET, 'picsize', FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
$info = filter_input(INPUT_GET, 'info', FILTER_SANITIZE_SPECIAL_CHARS) ?: 'false';


if (AUTH) {
    $auth = isset($_GET['auth']) ? $_GET['auth'] : '';
    if (in_array($type, ['url', 'pic', 'lrc'])) {
        if ($auth == '' || $auth != auth($server . $type . $id)) {
            http_response_code(403);
            exit;
        }
    }
}

// 数据格式
if (in_array($type, ['song', 'playlist', 'search'])) {
    header('content-type: application/json; charset=utf-8;');
} else if (in_array($type, ['name', 'lrc', 'artist'])) {
    header('content-type: text/plain; charset=utf-8;');
};

// 允许跨站
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// 禁止搜索引擎索引带参数的 URL
if (!empty($_GET)) {
    header("X-Robots-Tag: noindex, nofollow", true);
}

// 强制 HTTPS 重定向（在定义 Only_Https 后立即添加）
if (defined('Only_Https') && Only_Https === true) {
    // 检测是否为 HTTPS 请求（支持反向代理）
    $is_https = (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] == 1))
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    // 如果是 HTTP 请求，则重定向到 HTTPS
    if (!$is_https) {
        $https_url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        header('HTTP/1.1 302 Found');
        header('Location: ' . $https_url);
        exit;
    }
}

// include __DIR__ . '/vendor/autoload.php';
// you can use 'Meting.php' instead of 'autoload.php'
include __DIR__ . '/src/Meting.php';

use Metowolf\Meting;

$api = new Meting($server);
$api->format(true);

define('METING_API', true);

$qmck_file = __DIR__ . '/src/QMCookie.php';

if (file_exists($qmck_file)) {
    require $qmck_file;
    if (!empty($QMCookie) && $QMCookie != '' && $QMCookie != 'null' && $QMCookie != 'undefined' && $QMCookie != null) {
        $tencent_cookie = $QMCookie;
    } else {
        $tencent_cookie = 'local';
    }
} else {
    $tencent_cookie = 'local';
};

// 设置cookie
if ($server == 'netease') {
    $api->cookie('');
} else if ($server == 'tencent' && $tencent_cookie == 'local') {
    $api->cookie('');
} else if ($server == 'tencent' && $tencent_cookie != 'local') {
    $api->cookie($tencent_cookie);
} else {
    echo '{"error":"不支持的音乐源"}';
    exit;
};

if (($dwrc == 'true') || ($yrc == 'true') || ($qrc == 'true')) {
    $api->dwrc(true);
    $api->bakdwrc(false);
    $dwrc = 'true';
} else if (($dwrc == 'open') || ($yrc == 'open') || ($qrc == 'open')) {
    $api->dwrc(true);
    $api->bakdwrc(true);
    $dwrc = 'open';
} else {
    $api->dwrc(false);
    $api->bakdwrc(false);
    $dwrc = 'false';
};

if ($type == 'playlist') {

    if (CACHE) {
        $file_path = __DIR__ . '/cache/playlist/' . $server . '_' . $id . '.json';
        if (file_exists($file_path)) {
            if ($_SERVER['REQUEST_TIME'] - filemtime($file_path) < CACHE_TIME) {
                echo file_get_contents($file_path);
                exit;
            }
        }
    }

    $data = $api->playlist($id);
    if ($data == '[]') {
        echo '{"error":"id 为空,无法处理"}';
        exit;
    }
    $data = json_decode($data);

    // 修复:处理可能存在的额外数字索引层
    if (is_object($data) && isset($data->{'0'})) {
        // 如果是对象且有数字键,转换为数组
        $data = json_decode(json_encode($data), true);
        $data = array_values($data);
    } elseif (is_array($data) && count($data) > 0 && isset($data[0]) && !isset($data[0]->name)) {
        // 如果第一个元素不是歌曲对象,可能需要展平
        $data = array_values($data);
    }

    $playlist = array();
    foreach ($data as $song) {
        // 处理可能是数组的情况
        if (is_array($song)) {
            $song = (object)$song;
        }

        $lrc_url = API_URI . '?server=' . $song->source . '&type=lrc&id=' . $song->lyric_id . (AUTH ? '&auth=' . auth($song->source . 'lrc' . $song->lyric_id) : '');
        if ($dwrc == 'true') {
            $lrc_url .= '&dwrc=true';
        }

        $item = array(
            'name'   => $song->name,
            'artist' => implode('/', (array)$song->artist),
            'album'  => $song->album ?? '',
            'url'    => API_URI . '?server=' . $song->source . '&type=url&id=' . $song->url_id . (AUTH ? '&auth=' . auth($song->source . 'url' . $song->url_id) : ''),
            'pic'    => API_URI . '?server=' . $song->source . '&type=pic&id=' . $song->pic_id . (AUTH ? '&auth=' . auth($song->source . 'pic' . $song->pic_id) : ''),
            'lrc'    => $lrc_url
        );

        if (!empty($song->duration)) {
            $item['duration'] = $song->duration;
        }
        if (!empty($song->mv_id)) {
            $item['mv_id'] = $song->mv_id;
        }
        if (!empty($song->fee)) {
            $item['fee'] = $song->fee;
        }

        $playlist[] = $item;
    }

    $playlist = json_encode($playlist, JSON_UNESCAPED_UNICODE);

    if (CACHE) {
        // ! mkdir /cache/playlist
        file_put_contents($file_path, $playlist);
    }

    echo $playlist;
} else if ($type == 'search') {
    if (!isset($_GET['keyword'])) {
        echo '{"error":"请输入搜索关键词"}';
        exit;
    }

    $keyword = filter_input(INPUT_GET, 'keyword', FILTER_SANITIZE_SPECIAL_CHARS) ?: 'false';
    $option = array(
        'page' => isset($_GET['page']) ? $_GET['page'] : 1,
        'limit' => isset($_GET['limit']) ? $_GET['limit'] : 50,
    );

    $data = $api->search($keyword, $option);
    $data_array = json_decode($data, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo '{"error":"invalid JSON response"}';
        exit;
    }

    $search = array();
    foreach ($data_array as $song) {
        $lrc_url = API_URI . '?server=' . $song['source'] . '&type=lrc&id=' . $song['lyric_id'] . (AUTH ? '&auth=' . auth($song['source'] . 'lrc' . $song['lyric_id']) : '');
        if ($dwrc == 'true') {
            $lrc_url .= '&dwrc=true';
        }

        $entry = array(
            'name'   => $song['name'],
            'artist' => implode('/', $song['artist']),
            'album'  => $song['album'] ?? '',
            'url'    => API_URI . '?server=' . $song['source'] . '&type=url&id=' . $song['url_id'] . (AUTH ? '&auth=' . auth($song['source'] . 'url' . $song['url_id']) : ''),
            'pic'    => API_URI . '?server=' . $song['source'] . '&type=pic&id=' . $song['pic_id'] . (AUTH ? '&auth=' . auth($song['source'] . 'pic' . $song['pic_id']) : ''),
            'lrc'    => $lrc_url,
            'source' => $song['source']
        );
        if (!empty($song['duration'])) {
            $entry['duration'] = $song['duration'];
        }
        if (!empty($song['mv_id'])) {
            $entry['mv_id'] = $song['mv_id'];
        }
        if (!empty($song['fee'])) {
            $entry['fee'] = $song['fee'];
        }
        $search[] = $entry;
    }

    $search = json_encode($search, JSON_UNESCAPED_UNICODE);
    header('Content-Type: application/json; charset=utf-8');
    echo $search;
    exit;
} else if ($type == 'mv') {
    // MV 功能
    if ($info == 'true') {
        // 返回 JSON 格式的完整信息
        $mv_data_json = $api->mv($id, true);
        $mv_data = json_decode($mv_data_json, true);

        // 转换所有 URL 为 HTTPS
        if (isset($mv_data['url'])) {
            $mv_data['url'] = convert_to_https($mv_data['url']);
        }
        if (isset($mv_data['cover'])) {
            $mv_data['cover'] = convert_to_https($mv_data['cover']);
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($mv_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } else {
        // 返回纯 URL 进行 302 跳转
        $mv_url = $api->mv($id, false);
        if (!empty($mv_url) && $mv_url != 'null' && $mv_url != null) {
            $mv_url = convert_to_https($mv_url);
            header('HTTP/1.1 302 Temporary Redirect');
            header('Location: ' . $mv_url);
        } else {
            echo '{"error":"MV URL not found"}';
        }
    }
    exit;
} else if ($type == 'comment') {
    // 评论功能
    $limit = filter_input(INPUT_GET, 'limit', FILTER_SANITIZE_SPECIAL_CHARS) ?: 20;
    $offset = filter_input(INPUT_GET, 'offset', FILTER_SANITIZE_SPECIAL_CHARS) ?: 0;

    $comment_data = $api->comment($id, $limit, $offset);
    $comment_data = str_replace('http://', 'https://', $comment_data);
    header('Content-Type: application/json; charset=utf-8');
    echo $comment_data;
    exit;
} else if ($type == 'mvcomment') {
    // MV 评论功能
    $limit = filter_input(INPUT_GET, 'limit', FILTER_SANITIZE_SPECIAL_CHARS) ?: 20;
    $offset = filter_input(INPUT_GET, 'offset', FILTER_SANITIZE_SPECIAL_CHARS) ?: 0;

    $mvcomment_data = $api->mvcomment($id, $limit, $offset);
    $mvcomment_data = str_replace('http://', 'https://', $mvcomment_data);
    header('Content-Type: application/json; charset=utf-8');
    echo $mvcomment_data;
    exit;
} else {
    $need_song = !in_array($type, ['url', 'pic', 'lrc']);
    if ($need_song && !in_array($type, ['name', 'artist', 'song'])) {
        echo '{"error":"不支持的操作"}';
        exit;
    }

    if (!$need_song) {
        $data = song2data($api, null, $type, $id, $dwrc, $picsize, $br, $trlrc);
    } else {
        if (!isset($song)) $song = $api->song($id);
        if ($song == '[]') {
            echo '{"error":"unknown song"}';
            exit;
        }
        $data = song2data($api, json_decode($song)[0], $type, $id, $dwrc, $picsize, $br, $trlrc);
    }

    return_data($type, $data);
}

function api_uri() // static
{
    if (defined('Only_Https') && Only_Https === true) {
        $protocol = 'https://';
    } else {
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://');
    };

    return $protocol . $_SERVER['HTTP_HOST'] . strtok($_SERVER['REQUEST_URI'], '?');
}

function auth($name)
{
    return hash_hmac('sha1', $name, AUTH_SECRET);
}

function convert_to_https($url)
{
    if (empty($url)) {
        return $url;
    }

    // 网易云音乐特殊处理
    if (strlen($url) > 4 && $url[4] != 's' && strpos($url, 'http') === 0) {
        $url = str_replace('http', 'https', $url);
    }

    // 通用 HTTP 转 HTTPS
    if (strpos($url, 'http://') === 0) {
        $url = str_replace('http://', 'https://', $url);
    } elseif (strpos($url, 'https://') !== 0 && strpos($url, '://') !== false) {
        // 处理其他协议开头的 URL
        $url = 'https://' . ltrim($url, ':/');
    }

    return $url;
}

function song2data($api, $song, $type, $id, $dwrc, $picsize, $br, $trlrc)
{
    $data = '';
    switch ($type) {
        case 'name':
            $data = $song->name;
            break;

        case 'artist':
            $data = implode('/', $song->artist);
            break;

        case 'url':
            $m_url = json_decode($api->url($id, $br))->url;
            if ($m_url == '') break;
            // url format
            if ($api->server == 'netease') {
                if ($m_url[4] != 's') $m_url = str_replace('http', 'https', $m_url);
            };
            if (strpos($m_url, 'http://') === 0) {
                $m_url = str_replace('http://', 'https://', $m_url);
            } elseif (strpos($m_url, 'https://') !== 0) {
                $m_url = 'https://' . ltrim($m_url, ':/');
            };
            $data = $m_url;
            break;

        case 'pic':
            $data = json_decode($api->pic($id, $picsize))->url;
            if (strpos($data, 'http://') === 0) {
                $data = str_replace('http://', 'https://', $data);
            } elseif (strpos($data, 'https://') !== 0) {
                $data = 'https://' . ltrim($data, ':/');
            };
            break;

        case 'lrc':
            $lrc_json = $api->lyric($id);
            $lrc_data = json_decode($lrc_json);
            if (!$lrc_data || (!isset($lrc_data->lyric) && !isset($lrc_data->tlyric))) {
                $lrc = '';
            } else if ($trlrc == 'only' && $dwrc == 'false') {
                if ($lrc_data->tlyric == '') {
                    $lrc = '';
                } else {
                    $lrc_cn_arr = explode("\n", $lrc_data->tlyric);
                    foreach ($lrc_cn_arr as $i => $v) {
                        if ($v == '') continue;
                        $line = explode(']', $v, 2);
                        $line[1] = isset($line[1]) ? trim(preg_replace('/\s\s+/', ' ', $line[1] ?? '')) : '';
                        $lrc_cn_arr[$i] = $line[0] . ']' . $line[1];
                    }
                    $lrc = implode("\n", $lrc_cn_arr);
                }
            } else if ($lrc_data->tlyric == '') {
                $lrc = $lrc_data->lyric;
            } else if ($trlrc == 'true' && $dwrc == 'false') { // lyric_cn
                $lrc_arr = explode("\n", $lrc_data->lyric);
                $lrc_cn_arr = explode("\n", $lrc_data->tlyric);
                $lrc_cn_map = array();
                foreach ($lrc_cn_arr as $i => $v) {
                    if ($v == '') continue;
                    $line = explode(']', $v, 2);
                    // 格式化处理
                    $line[1] = isset($line[1]) ? trim(preg_replace('/\s\s+/', ' ', $line[1] ?? '')) : '';
                    $lrc_cn_map[$line[0]] = $line[1];
                    unset($lrc_cn_arr[$i]);
                }
                foreach ($lrc_arr as $i => $v) {
                    if ($v == '') continue;
                    $key = explode(']', $v, 2)[0];
                    if (!empty($lrc_cn_map[$key]) && $lrc_cn_map[$key] != '//') {
                        $lrc_arr[$i] .= ' (' . $lrc_cn_map[$key] . ')';
                        unset($lrc_cn_map[$key]);
                    }
                }
                $lrc = implode("\n", $lrc_arr);
            } else {
                $lrc = $lrc_data->lyric;
            }
            $data = $lrc;
            break;

        case 'song':
            $lrc_url = API_URI . '?server=' . $song->source . '&type=lrc&id=' . $song->lyric_id . (AUTH ? '&auth=' . auth($song->source . 'lrc' . $song->lyric_id) : '');
            if ($dwrc == 'true') {
                $lrc_url .= '&dwrc=true';
            }

            $data = array(
                'name'   => $song->name,
                'artist' => implode('/', $song->artist),
                'album'  => $song->album ?? '',
                'url'    => API_URI . '?server=' . $song->source . '&type=url&id=' . $song->url_id . (AUTH ? '&auth=' . auth($song->source . 'url' . $song->url_id) : ''),
                'pic'    => API_URI . '?server=' . $song->source . '&type=pic&id=' . $song->pic_id . (AUTH ? '&auth=' . auth($song->source . 'pic' . $song->pic_id) : ''),
                'lrc'    => $lrc_url,
                'source' => $song->source
            );
            if (!empty($song->duration)) {
                $data['duration'] = $song->duration;
            };
            if (!empty($song->mv_id)) {
                $data['mv_id'] = $song->mv_id;
            };
            if (!empty($song->fee)) {
                $data['fee'] = $song->fee;
            };
            $data = json_encode(array($data));
            break;
    }
    if ($data == '') exit;
    return $data;
}

function return_data($type, $data)
{
    if (in_array($type, ['url', 'pic'])) {
        header('HTTP/1.1 302 Temporary Redirect');
        header('Location: ' . $data);
    } else {
        echo $data;
    }
    exit;
}
