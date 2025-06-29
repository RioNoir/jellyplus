![jellyplus](https://raw.githubusercontent.com/NsCRio/jellyplus/refs/heads/main/src/img/banner-light.png)
# Jellyplus (Beta)
### Enhanced Jellyfin Media center

---

Jellyplus is open source software that takes advantage of Jellyfin's fantastic Media System and adds features so you can use it to its full potential. Jellyplus is not a direct fork of Jellyfin and is fully utilized in its “original” state, it is enhanced by taking advantage of the software's API, this allows you to always have the latest updated version of Jellyfin and use all its basic features.

## Installation

### Supported Architectures

Simply pulling `nscrio/jellyplus:latest` should retrieve the correct image for your arch, but you can also pull specific arch images via tags.

The architectures supported by this image are:

| Architecture | Available | Tag |
| :----: | :----: | ---- |
| x86-64 | ✅ | amd64-\<version tag\> |
| arm64 | ✅ | arm64v8-\<version tag\> |
| armhf | ❌ | |

### Install with docker run

```
docker run --name Jellyplus -e PUID=1000 -e GUID=1000 -e TZ=Europe/Rome -v ./data:/data -p 8096:8096 -d nscrio/jellyplus:latest
```

### Install with docker-compose (recommended)

```
version: '3'
services:
  jellyplus:
    image: nscrio/jellyplus:latest
    container_name: Jellyplus
    restart: unless-stopped
    environment:
      - PUID=1000
      - GUID=1000
      - TZ=Europe/Rome
    volumes:
      - ./data:/data
    ports:
      - "8096:8096"
```

## Application Setup

Webui can be found at `http://<your-ip>:8096`

More information can be found on the official Jellyfin documentation [here](https://jellyfin.org/docs/).

## Features

By default, Jellyfin allows you to manually add media content to the library for convenient viewing. Unfortunately, this system can be limiting in some cases, which is why we have added features that can come in handy:

- **Online search for media content** (currently only video, such as Movies and TV Series), using the TMDB API and the catalogs in the Addons. This allows you to automatically create the folders and subfolders needed for Jellyfin to be able to play content: NB: this does not in any way allow you to view in content if you do not have it, it is just an automation for folder structure.
- **Automatic content update** (currently only video, such as Movies and TV Series). Using the TMDB API and Addons catalogs, Jellyplus can update the folder structure in case of new releases such as the release of new seasons or episodes of TV series.
- **Playback via external url**, already supported by default by Jellyfin via .strm files, but here it has been made significantly easier to handle. It is possible to enter the link to be played directly in the metadata in the “Jellyplus Stream (Direct Url)” field, by doing this Jellyplus will know that it should automatically start the content from that url. This url supports dynamic parameters.
- **Addons**, are very similar to classic Jellyfin Plugins, but unlike the latter they are not used to extend functions to Jellyfin but to insert well-structured multimedia content predefined by Jellyplus. For example, it is possible to import a “Catalog” Addon to view content directly in Jellyfin that can be added to the library, or it is possible to signal to Jellyfin for that content which source it can be played from.
- **Discover**, that is a function that is shown from the moment there are Addons with catalogs, it allows you to explore these catalogs and find content that can be added to the library.

View full Documentation [here](https://github.com/NsCRio/jellyplus/blob/main/docs/DOCUMENTATION.md).

## Supported Clients

Basically all Jellyfin clients are supported, but not all of them fully support the features of Jellyplus. This is a list of the ones we recommend:

- **Jellyfin Official App**, supports all the additional features (search, discover etc.), it would seem to have some problems on Android.
- **VidHub**, supports almost all the additional features (search, discover etc.), works very well on all tested devices, is present almost everywhere.
- **Infuse** (Apple devices only), works as a normal Jellyfin client, much additional options of Jellyplus are not supported.

## Credits

- Many thanks to the [Jellyfin](https://jellyfin.org/) project for creating a phenomenal media center.
- This project uses the beautiful [ElegantFin](https://github.com/lscambo13/ElegantFin) as default theme for Jellyfin. Big thanks to his creator, [@lscambo13](https://github.com/lscambo13).
- This project uses [MediaFlow Proxy](https://github.com/mhdzumair/mediaflow-proxy/) in order to be able to play some videos and to proxy some streams. Big thanks to his creator, [@mhdzumair](https://github.com/mhdzumair).
- This project uses [Torrent Stream Server](https://github.com/KiraLT/torrent-stream-server) in order to be able to play some videos. Big thanks to his creator, [@KiraLT](https://github.com/KiraLT).
- This project uses [Laravel Tmdb](https://github.com/codebuglab/laravel-tmdb) in order to be able to use TMDB API. Big thanks to his creator, [@codebuglab](https://github.com/codebuglab).
- This project uses [LaLit's XML2Array](https://github.com/digitickets/lalit) . Big thanks to his creators, [@digitickets](https://github.com/digitickets).


## Disclaimer
Please be advised that this software functions as a regular Media Center and in no way contains any type of content that would violate the provisions of the Digital Millennium Copyright Act (DMCA). Anything that will be done through this software is at the complete discretion of the user. We are not responsible for the use that will be made of it.
