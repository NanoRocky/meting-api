<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
    <link rel="shortcut icon" href="favicon.png">
    <title>Meting-API</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aplayer/1.10.1/APlayer.min.css">
    <style>
        :root {
            --primary-color: rgba(0, 119, 255, 0.8);
            --must-primary-color: rgb(250, 92, 166);
            --bg-color: #F5F5F7;
            --card-bg: rgba(255, 255, 255, 0.8);
            --text-color: #1D1D1F;
            --code-bg: #F2F2F7;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", "Helvetica Neue", Arial, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            line-height: 1.6;
            margin: 0;
            padding: 40px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .container {
            width: 100%;
            max-width: 800px;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        h1 {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        h3,h6 {
            color: #86868B;
            font-weight: 400;
            margin-top: 0;
        }

        .card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 24px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.4);
        }

        h2 {
            font-size: 1.3rem;
            margin-top: 0;
            margin-bottom: 20px;
            border-left: 4px solid var(--primary-color);
            padding-left: 12px;
        }

        .param-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .param-item {
            margin-bottom: 20px;
        }

        .param-name {
            font-family: monospace;
            font-weight: 600;
            color: var(--primary-color);
            background: var(--code-bg);
            padding: 2px 6px;
            border-radius: 4px;
        }

        .must-param-name {
            font-family: monospace;
            font-weight: 600;
            color: var(--must-primary-color);
            background: var(--code-bg);
            padding: 2px 6px;
            border-radius: 4px;
        }

        .param-desc {
            margin-top: 8px;
            font-size: 0.95rem;
            color: #424245;
        }

        .param-desc {
            margin-top: 8px;
            font-size: 0.95rem;
            color: #424245;
        }

        .param-sub {
            margin-left: 24px;
            margin-top: 6px;
            font-size: 0.9rem;
            color: #6e6e73;
            position: relative;
        }

        .param-sub::before {
            content: '└─';
            position: absolute;
            left: -20px;
            color: #d2d2d7;
        }

        .level-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 0.85rem;
            background: rgba(0, 0, 0, 0.02);
            border-radius: 12px;
            overflow: hidden;
        }

        .level-table th,
        .level-table td {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .level-table th {
            background: rgba(0, 0, 0, 0.03);
            color: #86868B;
            font-weight: 500;
        }

        .example-link {
            display: block;
            background: var(--code-bg);
            color: var(--primary-color);
            padding: 10px 14px;
            border-radius: 8px;
            text-decoration: none;
            word-break: break-all;
            font-family: monospace;
            font-size: 0.85rem;
            margin-bottom: 8px;
            transition: opacity 0.2s;
        }

        .example-link:hover {
            opacity: 0.8;
        }

        a {
            color: var(--primary-color);
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aplayer/1.10.1/APlayer.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fetch-jsonp/build/fetch-jsonp.min.js"></script>
</head>

<script>
    const getPlayerList = async (server, type, id, dwrc) => {
        const res = await fetch(
            `<?php echo API_URI ?>?server=${server}&type=${type}&id=${id}&dwrc=${dwrc}`,
        );
        const data = await res.json();

        if (data[0].url.startsWith("@")) {
            // eslint-disable-next-line no-unused-vars
            const [handle, jsonpCallback, jsonpCallbackFunction, url] = data[0].url.split("@").slice(1);
            const jsonpData = await fetchJsonp(url).then((res) => res.json());
            const domain = (
                jsonpData.req_0.data.sip.find((i) => !i.startsWith("http://ws")) ||
                jsonpData.req_0.data.sip[0]
            ).replace("http://", "https://");

            return data.map((v, i) => ({
                name: v.name || v.title,
                artist: v.artist || v.author,
                url: domain + jsonpData.req_0.data.midurlinfo[i].purl,
                cover: v.cover || v.pic,
                lrc: v.lrc,
            }));
        } else {
            return data.map((v) => ({
                name: v.name || v.title,
                artist: v.artist || v.author,
                url: v.url,
                cover: v.cover || v.pic,
                lrc: v.lrc,
            }));
        }
    };

    const initPlayer = async () => {
        const playlist = await getPlayerList('netease', 'playlist', '2619366284', 'false');

        const ap = new APlayer({
            container: document.getElementById('aplayer'),
            audio: playlist,
            autoplay: true,
            fixed: true,
            volume: 0.7,
            mutex: true,
            loop: "all",
            order: "random",
            preload: "auto",
            lrcType: 3,
        });
    };

    window.addEventListener('DOMContentLoaded', async () => {
        await initPlayer();
    });
</script>

<body>
    <div id="aplayer"></div>
    <div class="container">
        <header class="header">
            <h1>Meting-API</h1>
            <h6>Meting Framework v<?= $meting_version ?> edit by NanoRocky</h6>
        </header>

        <section class="card">
            <h2>参数说明</h2>
            <div class="param-list">
                <div class="param-item">
                    <span class="param-name">server</span>
                    <div class="param-desc">数据源（Music Server）</div>
                    <div class="param-sub">&nbsp;<code>netease</code>&nbsp;网易云音乐&nbsp;(默认)</div>
                    <div class="param-sub">&nbsp;<code>tencent</code>&nbsp;QQ&nbsp;音乐</div>
                </div>
                <div class="param-item">
                    <span class="must-param-name">type</span>
                    <div class="param-desc">请求类型（Action Type）</div>
                    <div class="param-sub">&nbsp;<code>name</code>&nbsp;歌名</div>
                    <div class="param-sub">&nbsp;<code>artist</code>&nbsp;歌手</div>
                    <div class="param-sub">&nbsp;<code>url</code>&nbsp;链接</div>
                    <div class="param-sub">&nbsp;<code>pic</code>&nbsp;封面</div>
                    <div class="param-sub">&nbsp;<code>lrc</code>&nbsp;歌词</div>
                    <div class="param-sub">&nbsp;<code>song</code>&nbsp;单曲</div>
                    <div class="param-sub">&nbsp;<code>playlist</code>&nbsp;歌单</div>
                    <div class="param-sub">&nbsp;<code>search</code>&nbsp;搜索</div>
                </div>
                <div class="param-item">
                    <span class="must-param-name">id</span>
                    <div class="param-desc">资源唯一标识符（ID）</div>
                    <div class="param-sub">&nbsp;对应歌曲/歌单/封面的&nbsp;ID。</div>
                    <div class="param-sub">&nbsp;在使用其它功能&nbsp;比如搜索&nbsp;时，请将&nbsp;id&nbsp;指定为&nbsp;<code>0</code></div>
                </div>
                <div class="param-item">
                    <span class="param-name">picsize</span>
                    <div class="param-desc">歌曲封面大小</div>
                    <div class="param-sub">&nbsp;仅使用封面功能时&nbsp;可选&nbsp;携带，指定纯数字。</div>
                    <div class="param-sub">&nbsp;⚠&nbsp;Warning:&nbsp;目前&nbsp;QQMusic&nbsp;指定封面大小疑似会出现异常！</div>
                </div>
                <div class="param-item">
                    <span class="param-name">keyword</span>
                    <div class="param-desc">搜索关键词</div>
                    <div class="param-sub">&nbsp;仅使用搜索功能时携带。</div>
                </div>
                <div class="param-item">
                    <span class="param-name">br</span>
                    <div class="param-desc">最高音质</div>
                    <div class="param-sub">&nbsp;可选，指定后接口会按指定参数向下匹配寻找最高的音质返回。</div>
                    <div class="param-sub">&nbsp;具体支持的参数在下方列出。</div>

                </div>
                <div class="param-item">
                    <span class="param-name">dwrc</span>
                    <div class="param-desc">逐字歌词解析（Dynamic Word RC）</div>
                    <div class="param-sub">&nbsp;<code>false</code>&nbsp;禁用 (默认)</div>
                    <div class="param-sub">&nbsp;<code>true</code>&nbsp;优先解析并返回逐字歌词</div>
                    <div class="param-sub">&nbsp;<code>open</code>&nbsp;备用模式（若无逐字歌词则返回空）</div>
                </div>
                <div class="param-item">
                    <span class="param-name">trlrc</span>
                    <div class="param-desc">翻译歌词显示（Translate LRC）</div>
                    <div class="param-sub">&nbsp;<code>false</code>&nbsp;禁用 (默认)</div>
                    <div class="param-sub">&nbsp;<code>true</code>&nbsp;合并显示原文与翻译</div>
                    <div class="param-sub">&nbsp;<code>only</code>&nbsp;仅输出翻译歌词</div>
                </div>
            </div>
        </section>

        <section class="card">
            <h2>GitHub</h2>
            GitHub：<a href="https://github.com/injahow/meting-api" target="_blank">meting-api</a>，此API基于 <a href="https://github.com/metowolf/Meting" target="_blank">Meting</a> 构建。当前为<a href="https://github.com/NanoRocky/meting-api" target="_blank">酪灰修改版本</a>。
        </section>

        <section class="card">
            <h2>示例 URL</h2>
            例如：<a class="example-link" href="<?php echo API_URI ?>?server=netease&type=url&id=416892104" target="_blank"><?php echo API_URI ?>?server=netease&type=url&id=416892104</a>
            <a class="example-link" href="<?php echo API_URI ?>?server=netease&type=song&id=591321" target="_blank"><?php echo API_URI ?>?server=netease&type=song&id=591321</a>
            <a class="example-link" href="<?php echo API_URI ?>?server=netease&type=playlist&id=2619366284&dwrc=true" target="_blank"><?php echo API_URI ?>?server=netease&type=playlist&id=2619366284&dwrc=true</a>
            <a class="example-link" href="<?php echo API_URI ?>?server=netease&type=search&id=0&dwrc=true&keyword=寄往未来的信" target="_blank"><?php echo API_URI ?>?server=netease&type=search&id=0&dwrc=true&keyword=寄往未来的信</a>
            <a class="example-link" href="<?php echo API_URI ?>?server=tencent&type=search&id=0&dwrc=true&keyword=寄往未来的信" target="_blank"><?php echo API_URI ?>?server=tencent&type=search&id=0&dwrc=true&keyword=寄往未来的信</a>
        </section>

        <section class="card">
            <h2>音质参数说明</h2>
            <div class="param-sub">&nbsp;<b>Netease</b> (Weapi V1) 专用级别：
                <div class="param-sub">&nbsp;调用 Netease 时可直接将 <code>Level</code> 作为 <code>br</code> 参数使用。</div>
                <div class="param-sub">&nbsp;使用带有 ⚠ 符号的音质时，请确保设备具有对应的解码器！</div>
                <div class="param-sub">&nbsp;当选择的音质不可用时，会由接口自动向下匹配寻找可用的最高音质返回。
                    <div class="param-sub">&nbsp;依照网易云音乐开发者文档：
                        <div class="param-sub">&nbsp;原则：音效>音质</div>
                        <div class="param-sub">&nbsp;优先级：杜比全景声>沉浸环绕声>超清母带>高清臻音>hires>无损>极高>标准</div>
                    </div>
                </div>
                <table class="level-table">
                    <thead>
                        <tr>
                            <th>Level</th>
                            <th>br</th>
                            <th>对应级别</th>
                            <th>说明</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>standard</code></td>
                            <td> ≥ 128 </td>
                            <td>标准</td>
                            <td>128kbps</td>
                        </tr>
                        <tr>
                            <td><code>higher</code></td>
                            <td> ≥ 192 </td>
                            <td>较高</td>
                            <td>192kbps</td>
                        </tr>
                        <tr>
                            <td><code>exhigh</code></td>
                            <td> ≥ 320 </td>
                            <td>HQ</td>
                            <td>极高 320kbps</td>
                        </tr>
                        <tr>
                            <td><code>lossless</code></td>
                            <td> ≥ 999 </td>
                            <td>SQ</td>
                            <td>无损 FLAC</td>
                        </tr>
                        <tr>
                            <td><code>hires</code></td>
                            <td> ≥ 1999 </td>
                            <td>Hi-Res</td>
                            <td>高解析无损</td>
                        </tr>
                        <tr>
                            <td><code>jyeffect</code></td>
                            <td> ≥ 2999 </td>
                            <td>Spatial Audio</td>
                            <td>高清臻音（默认）</td>
                        </tr>
                        <tr>
                            <td><code>sky</code></td>
                            <td>null</td>
                            <td>Surround Audio</td>
                            <td>沉浸环绕声</td>
                        </tr>
                        <tr>
                            <td><code>vivid</code></td>
                            <td>null</td>
                            <td>Audio Vivid</td>
                            <td>⚠ 臻音全景声</td>
                        </tr>
                        <tr>
                            <td><code>dolby</code></td>
                            <td> ≥ 8999 </td>
                            <td>Dolby Atmos</td>
                            <td>⚠ 杜比全景声</td>
                        </tr>
                        <tr>
                            <td><code>jymaster</code></td>
                            <td> ≥ 9999 </td>
                            <td><b>Master</b></td>
                            <td>超清母带（无效时默认）</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="param-sub">&nbsp;<b>Tencent</b>：
                <div class="param-sub">&nbsp;使用带有 ⚠ 符号的音质时，请确保设备具有对应的解码器！</div>
                <div class="param-sub">&nbsp;仅当 <code>br</code> 处于 <code>3999 ~ 9998</code> 内，降级时允许匹配 臻品全景声 7.1、臻品全景声 5.1、NAC、DTS、Dolby 音质。</div>
                <div class="param-sub">&nbsp;调用 Tencent 时可直接将 <code>Level</code> 作为 <code>br</code> 参数使用。使用 Level 参数时，不执行音质降级。</div>
                <table class="level-table">
                    <thead>
                        <tr>
                            <th>Level</th>
                            <th>br</th>
                            <th>对应级别</th>
                            <th>说明</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>standard</code></td>
                            <td> ≥ 128 </td>
                            <td>标准</td>
                            <td>128kbps</td>
                        </tr>
                        <tr>
                            <td><code>higher</code></td>
                            <td> ≥ 192 </td>
                            <td>高品质</td>
                            <td>192kbps</td>
                        </tr>
                        <tr>
                            <td><code>exhigh</code></td>
                            <td> ≥ 320 </td>
                            <td>HQ</td>
                            <td>极高</td>
                        </tr>
                        <tr>
                            <td><code>lossless</code></td>
                            <td> ≥ 999 </td>
                            <td>SQ</td>
                            <td>无损（默认）</td>
                        </tr>
                        <tr>
                            <td><code>hires</code></td>
                            <td> ≥ 1999 </td>
                            <td>Hi-Res</td>
                            <td>臻品音质</td>
                        </tr>
                        <tr>
                            <td><code>hires5</code></td>
                            <td> ≥ 2999 </td>
                            <td>Hi-Res</td>
                            <td>臻品全景声 5.1</td>
                        </tr>
                        <tr>
                            <td><code>hires7</code></td>
                            <td> ≥ 3999 </td>
                            <td>Hi-Res</td>
                            <td>臻品全景声 7.1</td>
                        </tr>
                        <tr>
                            <td><code>nac</code></td>
                            <td> ≥ 5999 </td>
                            <td>AICodec NAC</td>
                            <td>⚠ 腾讯自研</td>
                        </tr>
                        <tr>
                            <td><code>dts</code></td>
                            <td> ≥ 7999 </td>
                            <td>DTS:X</td>
                            <td>⚠ DTS:X</td>
                        </tr>
                        <tr>
                            <td><code>dolby</code></td>
                            <td> ≥ 8999 </td>
                            <td>Dolby Atmos</td>
                            <td>⚠ 杜比全景声</td>
                        </tr>
                        <tr>
                            <td><code>jymaster</code></td>
                            <td> ≥ 9999 </td>
                            <td><b>Master</b></td>
                            <td>超清母带（无效时默认）</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</body>

</html>