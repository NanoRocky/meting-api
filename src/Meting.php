<?php

/**
 * Meting music framework
 * https://i-meto.com
 * https://github.com/metowolf/Meting
 * Version 1.5.11.
 *
 * Copyright 2019, METO Sheel <i@i-meto.com>
 * Released under the MIT license
 */

namespace Metowolf;

include __DIR__ . '/QrcDecode.php';

use QrcDecode\Decoder;

class Meting
{
    const VERSION = '1.5.11';

    public $raw;
    public $data;
    public $info = false;
    public $error;
    public $status;

    public $server;
    public $proxy = null;
    public $format = false;
    public $dwrc = false;
    public $bakdwrc = false;
    public $header;
    private $temp;

    public function dwrc($value = false)
    {
        $this->dwrc = $value;
        return $this;
    }

    public function bakdwrc($value = false)
    {
        $this->bakdwrc = $value;
        return $this;
    }

    public function __construct($value = 'netease')
    {
        $this->site($value);
    }

    public function site($value)
    {
        $suppose = array('netease', 'tencent');
        $this->server = in_array($value, $suppose) ? $value : 'netease';
        $this->header = $this->curlset();

        return $this;
    }

    public function cookie($value)
    {
        $this->header['Cookie'] = $value;

        return $this;
    }

    public function format($value = true)
    {
        $this->format = $value;

        return $this;
    }

    public function proxy($value)
    {
        $this->proxy = $value;

        return $this;
    }

    private function exec($api)
    {
        if (isset($api['encode'])) {
            $api = call_user_func_array(array($this, $api['encode']), array($api));
        }
        if ($api['method'] == 'GET') {
            if (isset($api['body'])) {
                $api['url'] .= '?' . http_build_query($api['body']);
                $api['body'] = null;
            }
        }

        $this->curl($api['url'], $api['body']);

        if (!$this->format) {
            return $this->raw;
        }

        $this->data = $this->raw;

        if (isset($api['decode'])) {
            $this->data = call_user_func_array(array($this, $api['decode']), array($this->data));
        }
        if (isset($api['format'])) {
            $this->data = $this->clean($this->data, $api['format']);
        }

        return $this->data;
    }

    private function curl($url, $payload = null, $headerOnly = 0)
    {
        $header = array_map(function ($k, $v) {
            return $k . ': ' . $v;
        }, array_keys($this->header), $this->header);
        $curl = curl_init();
        if (!is_null($payload)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, is_array($payload) ? http_build_query($payload) : $payload);
        }
        curl_setopt($curl, CURLOPT_HEADER, $headerOnly);
        curl_setopt($curl, CURLOPT_TIMEOUT, 20);
        curl_setopt($curl, CURLOPT_ENCODING, 'gzip');
        curl_setopt($curl, CURLOPT_IPRESOLVE, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        if ($this->proxy) {
            curl_setopt($curl, CURLOPT_PROXY, $this->proxy);
        }
        for ($i = 0; $i < 3; $i++) {
            $this->raw = curl_exec($curl);
            $this->info = curl_getinfo($curl);
            $this->error = curl_errno($curl);
            $this->status = $this->error ? curl_error($curl) : '';
            if (!$this->error) {
                break;
            }
        }
        /* delete before php 8.0 */
        // curl_close($curl);

        return $this;
    }

    private function pickup($array, $rule)
    {
        $t = explode('.', $rule);
        foreach ($t as $vo) {
            if (!isset($array[$vo])) {
                return array();
            }
            $array = $array[$vo];
        }

        return $array;
    }

    private function clean($raw, $rule)
    {
        $raw = json_decode($raw, true);
        if (!empty($rule)) {
            $raw = $this->pickup($raw, $rule);
        }
        if (!isset($raw[0]) && count($raw)) {
            $raw = array($raw);
        }
        $result = array_map(array($this, 'format_' . $this->server), $raw);

        return json_encode($result);
    }

    public function search($keyword, $option = null)
    {
        switch ($this->server) {
            case 'netease':
                $api = array(
                    'method' => 'POST',
                    'url'    => 'https://music.163.com/api/cloudsearch/pc',
                    'body'   => array(
                        's'      => $keyword,
                        /* TODO: 多功能搜索实现 */
                        'type'   => isset($option['type']) ? $option['type'] : 1,
                        'limit'  => isset($option['limit']) ? $option['limit'] : 50,
                        'total'  => 'true',
                        'offset' => isset($option['page']) && isset($option['limit']) ? ($option['page'] - 1) * $option['limit'] : 0,
                    ),
                    'encode' => 'netease_AESCBC',
                    'format' => 'result.songs',
                );
                break;
            case 'tencent':
                $api = array(
                    'method' => 'POST',
                    'url'    => 'https://u.y.qq.com/cgi-bin/musicu.fcg',
                    'body'   => json_encode(array(
                        'comm' => array(
                            'ct'   => '19',
                            'cv'   => '1859',
                            'uin'  => '0',
                        ),
                        'req' => array(
                            'method' => 'DoSearchForQQMusicDesktop',
                            'module' => 'music.search.SearchCgiService',
                            'param'  => array(
                                'grp'          => 1,
                                'num_per_page' => isset($option['limit']) ? (int)$option['limit'] : 50,
                                'page_num'     => isset($option['page']) ? (int)$option['page'] : 1,
                                'query'        => $keyword,
                                'search_type'  => 0,
                            ),
                        ),
                    )),
                    'format' => 'req.data.body.song.list',
                );
                break;
        }

        return $this->exec($api);
    }

    public function song($id)
    {
        switch ($this->server) {
            case 'netease':
                $api = array(
                    'method' => 'POST',
                    'url'    => 'https://music.163.com/api/v3/song/detail/',
                    'body'   => array(
                        'c' => '[{"id":' . $id . ',"v":0}]',
                    ),
                    'encode' => 'netease_AESCBC',
                    'format' => 'songs',
                );
                break;
            case 'tencent':
                $api = array(
                    'method' => 'POST',
                    'url'    => 'https://u.y.qq.com/cgi-bin/musicu.fcg',
                    'body'   => json_encode(array(
                        'comm' => array(
                            'ct' => '19',
                            'cv' => '1873',
                            'uin' => '0',
                        ),
                        'req_1' => array(
                            'method' => 'get_song_detail_yqq',
                            'module' => 'music.pf_song_detail_svr',
                            'param' => array(
                                'song_type' => 0,
                                'song_mid' => $id,
                                'song_id' => ''
                            )
                        )
                    )),
                    'format' => 'data',
                );
                break;
        }

        return $this->exec($api);
    }

    public function mv($id, $info = false)
    {
        switch ($this->server) {
            case 'netease':
                $api = array(
                    'method' => 'GET',
                    'url'    => 'https://music.163.com/api/mv/detail',
                    'body'   => array(
                        'id'  => $id,
                    ),
                );
                $raw_data = $this->exec($api);

                if (!$raw_data) {
                    return $raw_data;
                }

                $decoded_data = json_decode($raw_data, true);
                if (!isset($decoded_data['code']) || $decoded_data['code'] !== 200 || !isset($decoded_data['data'])) {
                    return $raw_data;
                }

                $mv_data = $decoded_data['data'];

                if (empty($mv_data['brs'])) {
                    return $info ? json_encode(array()) : null;
                }

                $brs = $mv_data['brs'];
                krsort($brs, SORT_NUMERIC);
                $best_url = reset($brs);

                if (!$info) {
                    return $best_url;
                }

                $result = array(
                    'name'       => $mv_data['name'],
                    'artistName' => $mv_data['artistName'],
                    'cover'      => $mv_data['cover'],
                    'url'        => $best_url,
                );
                return json_encode($result);

            case 'tencent':
                $api = array(
                    'method' => 'POST',
                    'url'    => 'https://u.y.qq.com/cgi-bin/musicu.fcg',
                    'body'   => json_encode(array(
                        'comm' => array(
                            'ct' => 6,
                            'cv' => 0,
                            'g_tk' => 1646675364,
                            'uin' => 0,
                            'format' => 'json',
                            'platform' => 'yqq'
                        ),
                        'mvInfo' => array(
                            'module' => 'music.video.VideoData',
                            'method' => 'get_video_info_batch',
                            'param' => array(
                                'vidlist' => array($id),
                                'required' => array('vid', 'type', 'sid', 'cover_pic', 'duration', 'singers', 'name', 'desc', 'playcnt', 'pubdate')
                            )
                        ),
                        'mvUrl' => array(
                            'module' => 'music.stream.MvUrlProxy',
                            'method' => 'GetMvUrls',
                            'param' => array(
                                'vids' => array($id),
                                'request_type' => 10003,
                                'addrtype' => 3,
                                'format' => 264,
                                'maxFiletype' => 60
                            )
                        )
                    )),
                );
                $raw_data = $this->exec($api);
                if (!$raw_data) return $raw_data;
                $decoded_data = json_decode($raw_data, true);

                $mv_urls = $decoded_data['mvUrl']['data'][$id]['mp4'] ?? [];
                if (empty($mv_urls)) return $info ? json_encode(array()) : null;

                $best_url = '';
                foreach (array_reverse($mv_urls) as $vo) {
                    if (!empty($vo['freeflow_url']) && !empty($vo['freeflow_url'][0])) {
                        $best_url = $vo['freeflow_url'][0];
                        break;
                    }
                }
                if (empty($best_url)) return $info ? json_encode(array()) : null;
                if (!$info) return $best_url;

                $mv_info = $decoded_data['mvInfo']['data'][$id] ?? [];
                $artists = array();
                if (isset($mv_info['singers'])) {
                    foreach ($mv_info['singers'] as $s) {
                        $artists[] = $s['name'];
                    }
                }
                $result = array(
                    'name'       => $mv_info['name'] ?? '',
                    'artistName' => implode(', ', $artists),
                    'cover'      => $mv_info['cover_pic'] ?? '',
                    'url'        => $best_url,
                );
                return json_encode($result);
        }
        return null;
    }

    public function comment($id, $limit = 20, $offset = 0)
    {
        switch ($this->server) {
            case 'netease':
                $api = array(
                    'method' => 'GET',
                    'url'    => 'https://music.163.com/api/v1/resource/comments/R_SO_4_' . $id,
                    'body'   => array(
                        'limit'  => $limit,
                        'offset' => $offset,
                        'format' => 'json',
                    ),
                );
                break;
            case 'tencent':
                $api = array(
                    'method' => 'GET',
                    'url'    => 'https://c.y.qq.com/base/fcgi-bin/fcg_global_comment_h5.fcg',
                    'body'   => array(
                        'cid'  => $id,
                        'platform' => 'yqq',
                        'format'   => 'json',
                    ),
                    // 'format' => 'data',
                );
                break;
        }

        return $this->exec($api);
    }

    public function mvcomment($id, $limit = 20, $offset = 0)
    {
        switch ($this->server) {
            case 'netease':
                $api = array(
                    'method' => 'GET',
                    'url'    => 'https://music.163.com/api/v1/resource/comments/R_MV_5_' . $id,
                    'body'   => array(
                        'limit'  => $limit,
                        'offset' => $offset,
                        'format' => 'json',
                    ),
                );
                break;
            case 'tencent':
                $api = array();
                break;
        }

        return $this->exec($api);
    }

    public function album($id)
    {
        switch ($this->server) {
            case 'netease':
                $api = array(
                    'method' => 'POST',
                    'url'    => 'https://music.163.com/api/v1/album/' . $id,
                    'body'   => array(
                        'total'         => 'true',
                        'offset'        => '0',
                        'id'            => $id,
                        'limit'         => '100000',
                        'ext'           => 'true',
                        'private_cloud' => 'true',
                    ),
                    'encode' => 'netease_AESCBC',
                    'format' => 'songs',
                );
                break;
            case 'tencent':
                $api = array(
                    'method' => 'GET',
                    'url'    => 'https://i.y.qq.com/v8/fcg-bin/fcg_v8_album_info_cp.fcg',
                    'body'   => array(
                        'albummid'     => $id,
                        'g_tk'         => 5381,
                        'uin'          => 0,
                        'format'       => 'json',
                        'inCharset'    => 'utf-8',
                        'outCharset'   => 'utf-8',
                        'notice'       => 0,
                        'platform'     => 'h5',
                        'needNewCode'  => 1,
                    ),
                    'format' => 'data.list',
                );
                break;
        }

        return $this->exec($api);
    }

    public function artist($id, $limit = 50)
    {
        switch ($this->server) {
            case 'netease':
                $api = array(
                    'method' => 'POST',
                    'url'    => 'https://music.163.com/api/v1/artist/' . $id,
                    'body'   => array(
                        'ext'           => 'true',
                        'private_cloud' => 'true',
                        'top'           => $limit,
                        'id'            => $id,
                    ),
                    'encode' => 'netease_AESCBC',
                    'format' => 'hotSongs',
                );
                break;
            case 'tencent':
                $api = array(
                    'method' => 'POST',
                    'url'    => 'https://u.y.qq.com/cgi-bin/musicu.fcg',
                    'body'   => json_encode(array(
                        'comm' => array(
                            'ct'   => 24,
                            'cv'   => 0,
                        ),
                        'req' => array(
                            'module' => 'music.web_singer_info_svr',
                            'method' => 'get_singer_detail_info',
                            'param'  => array(
                                'sort'      => 5,
                                'singermid' => $id,
                                'sin'       => 0,
                                'num'       => (int)$limit,
                            ),
                        ),
                    )),
                    'format' => 'req.data.songlist',
                );
                break;
        }

        return $this->exec($api);
    }

    public function playlist($id)
    {
        switch ($this->server) {
            case 'netease':
                $api = [
                    "method" => "POST",
                    "url" => "https://music.163.com/api/v6/playlist/detail",
                    "body" => [
                        "id" => $id,
                        "offset" => "0",
                        "total" => "True",
                        "limit" => "100000",
                        "n" => "100000",
                    ],
                    "encode" => "netease_AESCBC",
                ];
                $playlistData = json_decode($this->exec($api), true);
                if (!isset($playlistData["playlist"]["trackIds"])) {
                    return json_encode([]);
                }
                $trackIds = $playlistData["playlist"]["trackIds"];
                $allTracks = [];
                $idBatches = array_chunk(array_column($trackIds, "id"), 500);

                foreach ($idBatches as $batch) {
                    // Convert the batch into the proper format
                    $songIds = array_map(function ($id) {
                        return ["id" => $id, "v" => 0]; // Using 'v' => 0 as per your suggestion
                    }, $batch);

                    // Fetch the song details for each batch
                    $songApi = [
                        "method" => "POST",
                        "url" => "https://music.163.com/api/v3/song/detail/",
                        "body" => [
                            "c" => json_encode($songIds),
                        ],
                        "encode" => "netease_AESCBC",
                    ];
                    $songData = $this->exec($songApi);
                    // Merge songs data into allTracks
                    $allTracks = array_merge(
                        $allTracks,
                        json_decode($songData, true)["songs"]
                    );
                }
                $tmp = array_map(function ($data) {
                    $result = [
                        "id" => $data["id"],
                        "name" => $data["name"],
                        "artist" => [],
                        "album" => isset($data["al"]["name"]) ? $data["al"]["name"] : $data["album"],
                        // "pic_id" => isset($data["al"]["pic_str"])
                        //     ? $data["al"]["pic_str"]
                        //     : $data["al"]["pic"],
                        "pic_id" => $data["al"]["pic_str"] ?? $data["al"]["pic"] ?? $data["pic_id"],
                        "url_id" => $data["id"],
                        "lyric_id" => $data["id"],
                        "source" => "netease",
                    ];
                    if (isset($data["al"]["picUrl"])) {
                        preg_match(
                            "/\/(\d+)\./",
                            $data["al"]["picUrl"],
                            $match
                        );
                        $result["pic_id"] = $match[1];
                    }
                    foreach ($data["ar"] as $vo) {
                        $result["artist"][] = $vo["name"];
                    }
                    return $result;
                }, $allTracks);
                return json_encode($tmp);
            case 'tencent':
                $api = array(
                    'method' => 'GET',
                    'url'    => 'https://i.y.qq.com/qzone-music/fcg-bin/fcg_ucc_getcdinfo_byids_cp.fcg',
                    'body'   => array(
                        'disstid'      => $id,
                        'type'         => 1,
                        'json'         => 1,
                        'utf8'         => 1,
                        'onlysong'     => 0,
                        'nosign'       => 1,
                        'g_tk'         => 5381,
                        'loginUin'     => 0,
                        'hostUin'      => 0,
                        'format'       => 'json',
                        'inCharset'    => 'GB2312',
                        'outCharset'   => 'utf-8',
                        'notice'       => 0,
                        'platform'     => 'yqq',
                        'needNewCode'  => 0,
                    ),
                    'format' => 'cdlist.0.songlist',
                );
                break;
        }

        return $this->exec($api);
    }

    public function url($id, $br = 2147483)
    {
        switch ($this->server) {
            case 'netease':
                $api = array(
                    'method' => 'POST',
                    'url'    => 'https://music.163.com/api/song/enhance/player/url',
                    'body'   => array(
                        'ids' => array($id),
                        'br'  => $br * 1000,
                    ),
                    'encode' => 'netease_AESCBC',
                    'decode' => 'netease_url',
                );
                break;
            case 'tencent':
                $api = array(
                    'method' => 'POST',
                    'url'    => 'https://u.y.qq.com/cgi-bin/musicu.fcg',
                    'body'   => json_encode(array(
                        'comm' => array(
                            'ct' => '19',
                            'cv' => '1873',
                            'uin' => '0',
                        ),
                        'req_1' => array(
                            'method' => 'get_song_detail_yqq',
                            'module' => 'music.pf_song_detail_svr',
                            'param' => array(
                                'song_type' => 0,
                                'song_mid' => $id,
                                'song_id' => ''
                            )
                        )
                    )),
                    'decode' => 'tencent_url',
                );
                break;
        }
        $this->temp['br'] = $br;

        return $this->exec($api);
    }

    public function lyric($id)
    {
        switch ($this->server) {
            case 'netease':
                $api = array(
                    'method' => 'POST',
                    'url'    => 'https://music.163.com/api/song/lyric',
                    'body'   => array(
                        'id'        => $id,
                        'os'        => 'pc',
                        'lv'        => -1,
                        'kv'        => -1,
                        'tv'        => -1,
                        'rv'        => -1,
                        'yv'        => 1,
                        'showRole'  => 'False',
                        'cp'        => 'False',
                        'e_r'       => 'False',
                    ),
                    'encode' => 'netease_AESCBC',
                );
                // 发送请求，获取结果
                $result = $this->exec($api);
                $tmp = json_decode($result, true);
                // 手动处理返回结果
                if (isset($tmp['yrc'])) {
                    // 如果存在 yrc 字段，进一步判断 dwrc 参数的值
                    if ($this->dwrc) {
                        // dwrc 参数为 true，手动处理 netease_lyric_yrc 解析
                        $decoded_result = $this->netease_lyric_yrc($result);
                    } else {
                        // dwrc 参数为 false，手动处理普通 netease_lyric 解析
                        $decoded_result = $this->netease_lyric($result);
                    }
                } else {
                    if ($this->bakdwrc) {
                        // 如果打开了备用模式，不存在逐字歌词直接返回空。
                        $decoded_result = null;
                    } else {
                        // 不存在 yrc 字段，直接执行普通 netease_lyric 解析
                        $decoded_result = $this->netease_lyric($result);
                    }
                }
                // 返回最终处理后的结果
                return $decoded_result;
            case 'tencent':
                $api = array(
                    'method' => 'POST',
                    'url'    => 'https://u6.y.qq.com/cgi-bin/musicu.fcg',
                    'body'   => json_encode(array(
                        'comm' => array(
                            '_channelid' => '0',
                            '_os_version' => '6.2.9200-2',
                            'authst' => '',
                            'ct' => '19',
                            'cv' => '1873',
                            'patch' => '118',
                            'psrf_access_token_expiresAt' => 0,
                            'psrf_qqaccess_token' => '',
                            'psrf_qqopenid' => '',
                            'psrf_qqunionid' => '',
                            'tmeAppID' => 'qqmusic',
                            'tmeLoginType' => 2,
                            'uin' => '0',
                            'wid' => '0',
                        ),
                        'req_1' => array(
                            'method' => 'GetPlayLyricInfo',
                            'module' => 'music.musichallSong.PlayLyricInfo',
                            'param' => array(
                                'songMID' => $id,
                                'qrc' => $this->dwrc ? 1 : 0,
                            ),
                        )
                    )),
                    'decode' => 'tencent_lyric_qrc',
                );
                break;
        }
        return $this->exec($api);
    }

    public function pic($id, $size)
    {
        switch ($this->server) {
            case 'netease':
                if (isset($size) && !empty($size)) {
                    $url = 'https://p3.music.126.net/' . $this->netease_encryptId($id) . '/' . $id . '.jpg?param=' . $size . 'x' . $size;
                } else {
                    $url = 'https://p3.music.126.net/' . $this->netease_encryptId($id) . '/' . $id . '.jpg';
                };
                break;
            case 'tencent':
                if (isset($size) && !empty($size)) {
                    $url = 'https://y.gtimg.cn/music/photo_new/T002R' . $size . 'x' . $size . 'M000' . $id . '.jpg';
                } else {
                    $url = 'https://y.gtimg.cn/music/photo_new/T002M000' . $id . '.jpg';
                };
                break;
        }

        return json_encode(array('url' => $url));
    }

    private function curlset()
    {
        switch ($this->server) {
            case 'netease':
                return array(
                    'Referer'         => 'https://music.163.com/',
                    'Cookie'          => 'appver=8.2.30; os=iPhone OS; osver=15.0; EVNSM=1.0.0; buildver=2206; channel=distribution; machineid=iPhone13.3',
                    'User-Agent'      => 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 CloudMusic/0.1.1 NeteaseMusic/8.2.30',
                    'X-Real-IP'       => long2ip(mt_rand(1884815360, 1884890111)),
                    'Accept'          => '*/*',
                    'Accept-Language' => 'zh-CN,zh;q=0.8,gl;q=0.6,zh-TW;q=0.4',
                    'Connection'      => 'keep-alive',
                    'Content-Type'    => 'application/x-www-form-urlencoded',
                );
            case 'tencent':
                return array(
                    'Referer'         => 'https://y.qq.com/',
                    'Cookie'          => 'pgv_pvi=22038528; pgv_si=s3156287488; pgv_pvid=5535248600; yplayer_open=1; ts_last=y.qq.com/portal/player.html; ts_uid=4847550686; yq_index=0; qqmusic_fromtag=66; player_exist=1',
                    'User-Agent'      => 'QQ%E9%9F%B3%E4%B9%90/54409 CFNetwork/901.1 Darwin/17.6.0 (x86_64)',
                    'Accept'          => '*/*',
                    'Accept-Language' => 'zh-CN,zh;q=0.8,gl;q=0.6,zh-TW;q=0.4',
                    'Connection'      => 'keep-alive',
                    'Content-Type'    => 'application/x-www-form-urlencoded',
                );
        }
    }

    private function getRandomHex($length)
    {
        if (function_exists('random_bytes')) {
            return bin2hex(random_bytes($length / 2));
        }
        if (function_exists('openssl_random_pseudo_bytes')) {
            return bin2hex(openssl_random_pseudo_bytes($length / 2));
        }
    }

    private function bchexdec($hex)
    {
        $dec = 0;
        $len = strlen($hex);
        for ($i = 1; $i <= $len; $i++) {
            $dec = bcadd($dec, bcmul(strval(hexdec($hex[$i - 1])), bcpow('16', strval($len - $i))));
        }

        return $dec;
    }

    private function bcdechex($dec)
    {
        $hex = '';
        do {
            $last = bcmod($dec, 16);
            $hex = dechex($last) . $hex;
            $dec = bcdiv(bcsub($dec, $last), 16);
        } while ($dec > 0);

        return $hex;
    }

    private function str2hex($string)
    {
        $hex = '';
        for ($i = 0; $i < strlen($string); $i++) {
            $ord = ord($string[$i]);
            $hexCode = dechex($ord);
            $hex .= substr('0' . $hexCode, -2);
        }

        return $hex;
    }

    private function netease_AESCBC($api)
    {
        $modulus = '157794750267131502212476817800345498121872783333389747424011531025366277535262539913701806290766479189477533597854989606803194253978660329941980786072432806427833685472618792592200595694346872951301770580765135349259590167490536138082469680638514416594216629258349130257685001248172188325316586707301643237607';
        $pubkey = '65537';
        $nonce = '0CoJUm6Qyw8W8jud';
        $vi = '0102030405060708';

        if (extension_loaded('bcmath')) {
            $skey = $this->getRandomHex(16);
        } else {
            $skey = 'B3v3kH4vRPWRJFfH';
        }

        $body = json_encode($api['body']);

        if (function_exists('openssl_encrypt')) {
            // 使用 openssl_encrypt 进行加密
            $pad = 16 - (strlen($body) % 16);
            $body = $body . str_repeat(chr($pad), $pad);
            $body = openssl_encrypt($body, 'aes-128-cbc', $nonce, OPENSSL_RAW_DATA, $vi);
            $body = base64_encode($body);

            $pad = 16 - (strlen($body) % 16);
            $body = $body . str_repeat(chr($pad), $pad);
            $body = openssl_encrypt($body, 'aes-128-cbc', $skey, OPENSSL_RAW_DATA, $vi);
            $body = base64_encode($body);
        }


        if (extension_loaded('bcmath')) {
            $skey = strrev(mb_convert_encoding($skey, 'UTF-8', 'ISO-8859-1'));
            $skey = $this->bchexdec($this->str2hex($skey));
            $skey = bcpowmod($skey, $pubkey, $modulus);
            $skey = $this->bcdechex($skey);
            $skey = str_pad($skey, 256, '0', STR_PAD_LEFT);
        } else {
            $skey = '85302b818aea19b68db899c25dac229412d9bba9b3fcfe4f714dc016bc1686fc446a08844b1f8327fd9cb623cc189be00c5a365ac835e93d4858ee66f43fdc59e32aaed3ef24f0675d70172ef688d376a4807228c55583fe5bac647d10ecef15220feef61477c28cae8406f6f9896ed329d6db9f88757e31848a6c2ce2f94308';
        }

        $api['url'] = str_replace('/api/', '/weapi/', $api['url']);
        $api['body'] = array(
            'params'    => $body,
            'encSecKey' => $skey,
        );

        return $api;
    }

    private function netease_encryptId($id)
    {
        $magic = str_split('3go8&$8*3*3h0k(2)2');
        $song_id = str_split($id);
        for ($i = 0; $i < count($song_id); $i++) {
            $song_id[$i] = chr(ord($song_id[$i]) ^ ord($magic[$i % count($magic)]));
        }
        $result = base64_encode(md5(implode('', $song_id), 1));
        $result = str_replace(array('/', '+'), array('_', '-'), $result);

        return $result;
    }

    private function netease_url($result)
    {
        $data = json_decode($result, true);
        if (isset($data['data'][0]['uf']['url'])) {
            $data['data'][0]['url'] = $data['data'][0]['uf']['url'];
        }
        if (isset($data['data'][0]['url'])) {
            $url = array(
                'url'  => $data['data'][0]['url'],
                'size' => $data['data'][0]['size'],
                'br'   => $data['data'][0]['br'] / 1000,
            );
        } else {
            $url = array(
                'url'  => '',
                'size' => 0,
                'br'   => -1,
            );
        }

        return json_encode($url);
    }

    private function tencent_url($result)
    {
        $data = json_decode($result, true);
        if (isset($data['req_1']['data']['track_info'])) {
            $track = $data['req_1']['data']['track_info'];
        } else {
            $track = $data['data'][0];
        }
        $guid = mt_rand() % 10000000000;

        $type = array(
            array('size_flac', 999999, 'F000', 'flac'),
            array('size_320mp3', 320, 'M800', 'mp3'),
            array('size_192aac', 192, 'C600', 'm4a'),
            array('size_128mp3', 128, 'M500', 'mp3'),
            array('size_96aac', 96, 'C400', 'm4a'),
            array('size_48aac', 48, 'C200', 'm4a'),
            array('size_24aac', 24, 'C100', 'm4a'),
        );

        $uin = '0';
        preg_match('/uin=(\d+)/', $this->header['Cookie'], $uin_match);
        if (count($uin_match)) {
            $uin = $uin_match[1];
        }

        $payload = array(
            'req_0' => array(
                'module' => 'vkey.GetVkeyServer',
                'method' => 'CgiGetVkey',
                'param'  => array(
                    'guid'      => (string) $guid,
                    'songmid'   => array(),
                    'filename'  => array(),
                    'songtype'  => array(),
                    'uin'       => $uin,
                    'loginflag' => 1,
                    'platform'  => '20',
                ),
            ),
        );

        foreach ($type as $vo) {
            $payload['req_0']['param']['songmid'][] = $track['mid'];
            $payload['req_0']['param']['filename'][] = $vo[2] . $track['file']['media_mid'] . '.' . $vo[3];
            $payload['req_0']['param']['songtype'][] = $track['type'];
        }

        $api = array(
            'method' => 'POST',
            'url'    => 'https://u.y.qq.com/cgi-bin/musicu.fcg',
            'body'   => json_encode(array(
                'comm' => array(
                    'ct' => '19',
                    'cv' => '1873',
                    'uin' => '0',
                ),
                'req_0' => $payload['req_0']
            )),
        );
        $response = json_decode($this->exec($api), true);
        $vkeys = $response['req_0']['data']['midurlinfo'];

        foreach ($type as $index => $vo) {
            if (isset($track['file'][$vo[0]]) && $track['file'][$vo[0]] && $vo[1] <= $this->temp['br']) {
                if (!empty($vkeys[$index]['vkey'])) {
                    $sip = isset($response['req_0']['data']['sip'][0]) ? $response['req_0']['data']['sip'][0] : 'https://dl.stream.qqmusic.qq.com/';
                    $url = array(
                        'url'  => $sip . $vkeys[$index]['purl'],
                        'size' => $track['file'][$vo[0]],
                        'br'   => $vo[1],
                    );
                    break;
                }
            }
        }
        if (!isset($url['url'])) {
            $url = array(
                'url'  => '',
                'size' => 0,
                'br'   => -1,
            );
        }

        return json_encode($url);
    }

    private function netease_lyric_yrc($result)
    {
        // 检查 $result 是否需要解码
        if (is_string($result)) {
            $result = json_decode($result, true);
        }

        $data = array(
            'lyric'  => isset($result['yrc']['lyric']) ? $result['yrc']['lyric'] : '',
            'tlyric' => isset($result['none']['lyric']) ? $result['none']['lyric'] : '',
        );

        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    private function netease_lyric($result)
    {
        // 检查 $result 是否需要解码
        if (is_string($result)) {
            $result = json_decode($result, true);
        }

        $data = array(
            'lyric'  => isset($result['lrc']['lyric']) ? $result['lrc']['lyric'] : '',
            'tlyric' => isset($result['tlyric']['lyric']) ? $result['tlyric']['lyric'] : '',
        );

        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    private function tencent_lyric_qrc($result)
    {
        $result = json_decode($result, true);
        $lrc = $result['req_1']['data']['lyric'];
        if ($result['req_1']['data']['qrc'] == 0) {
            return json_encode(array(
                'lyric'  => isset($result['req_1']['data']['lyric']) && $result['req_1']['data']['lyric'] ? base64_decode($result['req_1']['data']['lyric']) : '',
                'tlyric' => isset($result['req_1']['data']['trans']) && $result['req_1']['data']['trans'] ? base64_decode($result['req_1']['data']['trans']) : '',
            ), JSON_UNESCAPED_UNICODE);
        }
        $decoder = new Decoder();
        $xmlContent = $decoder->decode($lrc);
        $pattern = '/<Lyric_1\s+[^>]*LyricContent="([^"]+)"/';
        preg_match($pattern, $xmlContent, $matches);
        $pureLyric = isset($matches[1]) ? html_entity_decode($matches[1]) : '';
        $qrcf = json_encode(['lyric'  => $pureLyric, 'tlyric' => ''], JSON_UNESCAPED_UNICODE);
        return $qrcf;
    }

    private function tencent_lyric($result)
    {
        $result = trim($result);
        if (substr($result, 0, 1) !== '{') {
            $result = preg_replace('/^\w+\(/', '', $result);
            $result = preg_replace('/\)$/', '', $result);
        }
        $result = json_decode($result, true);
        $data = array(
            'lyric'  => isset($result['lyric']) ? base64_decode($result['lyric']) : '',
            'tlyric' => isset($result['trans']) ? base64_decode($result['trans']) : '',
        );

        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    protected function format_netease($data)
    {
        $result = array(
            'id'       => $data['id'],
            'name'     => $data['name'],
            'artist'   => array(),
            'album'    => $data['al']['name'],
            'pic_id'   => isset($data['al']['pic_str']) ? $data['al']['pic_str'] : $data['al']['pic'],
            'url_id'   => $data['id'],
            'lyric_id' => $data['id'],
            'duration' => $data['dt'] ?? null,
            'mv_id'    => $data['mv'] ?? null,
            'fee'      => $data['fee'] ?? null,
            'source'   => 'netease',
        );
        if (isset($data['al']['picUrl'])) {
            preg_match('/\/(\d+)\./', $data['al']['picUrl'], $match);
            $result['pic_id'] = $match[1];
        }
        foreach ($data['ar'] as $vo) {
            $result['artist'][] = $vo['name'];
        }

        return $result;
    }

    protected function format_tencent($data)
    {
        if (isset($data['musicData'])) {
            $data = $data['musicData'];
        }

        $mid = $data['mid'] ?? ($data['songmid'] ?? '');
        $name = $data['name'] ?? ($data['songname'] ?? '');

        $album_name = $data['album']['title'] ?? ($data['album']['name'] ?? ($data['albumname'] ?? ''));
        $album_mid = $data['album']['mid'] ?? ($data['albummid'] ?? '');

        $interval = $data['interval'] ?? null;
        $mv_id = $data['mv']['vid'] ?? ($data['vid'] ?? null);
        $fee = $data['pay']['payplay'] ?? ($data['pay']['pay_play'] ?? null);

        $result = array(
            'id'       => $mid,
            'name'     => $name,
            'artist'   => array(),
            'album'    => trim($album_name),
            'pic_id'   => $album_mid,
            'url_id'   => $mid,
            'lyric_id' => $mid,
            'duration' => $interval,
            'mv_id'    => $mv_id,
            'fee'      => $fee,
            'source'   => 'tencent',
        );
        if (isset($data['singer'])) {
            foreach ($data['singer'] as $vo) {
                $result['artist'][] = $vo['name'];
            }
        }

        return $result;
    }
}
