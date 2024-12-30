<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
    <link rel="shortcut icon" href="favicon.png">
    <title>Meting-API</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aplayer/1.10.1/APlayer.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aplayer/1.10.1/APlayer.min.js"></script>
</head>

<script>
    const getPlayerList = async (server, type, id, yrc) => {
        const res = await fetch(
            `<?php echo API_URI ?>?server=netease&type=playlist&id=2619366284&yrc=false`,
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
        const playlist = await getPlayerList('netease', 'playlist', '2619366284');

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
    <h1>Meting-API</h1>
    <h2>参数说明</h2>
    &nbsp;&nbsp;&nbsp;server: 数据源
    <br />
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;netease 网易云音乐(默认)<br />
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;tencent QQ音乐<br />
    <br />
    &nbsp;&nbsp;&nbsp;type: 类型<br />
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;name 歌曲名<br />
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;artist 歌手<br />
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;url 链接<br />
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;pic 封面<br />
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;lrc 歌词<br />
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;song 单曲<br />
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;playlist 歌单<br />
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;search 搜索<br />
    <br />
    &nbsp;&nbsp;&nbsp;id: 类型ID（封面ID/单曲ID/歌单ID）<br />
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;（id 必须指定，在使用其它功能 比如搜索 时，请将 id 指定为 0 ）<br />
    <br />
    &nbsp;&nbsp;&nbsp;keyword: 搜索关键词（仅使用搜索功能时携带）<br />
    <br />
    &nbsp;&nbsp;&nbsp;yrc: 网易云音乐逐字歌词解析开关，开启后优先解析逐字歌词。<br />
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;False 禁用(默认)<br />
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;True 启用<br />
    <br /><br />
    GitHub：<a href="https://github.com/injahow/meting-api" target="_blank">meting-api</a>，此API基于 <a href="https://github.com/metowolf/Meting" target="_blank">Meting</a> 构建。当前为<a href="https://github.com/NanoRocky/meting-api" target="_blank">酪灰修改版本</a>。<br /><br /><br />
    例如：<a href="<?php echo API_URI ?>?server=netease&type=url&id=416892104" target="_blank"><?php echo API_URI ?>?server=netease&type=url&id=416892104</a><br />
    <a href="<?php echo API_URI ?>?server=netease&type=song&id=591321" target="_blank" style="padding-left:48px"><?php echo API_URI ?>?server=netease&type=song&id=591321</a><br />
    <a href="<?php echo API_URI ?>?server=netease&type=playlist&id=2619366284&yrc=true" target="_blank" style="padding-left:48px"><?php echo API_URI ?>?server=netease&type=playlist&id=2619366284&yrc=true</a><br />
    <a href="<?php echo API_URI ?>?server=netease&type=search&id=0&yrc=true&keyword=寄往未来的信" target="_blank" style="padding-left:48px"><?php echo API_URI ?>?server=netease&type=search&id=0&yrc=true&keyword=寄往未来的信</a><br />
</body>

</html>
