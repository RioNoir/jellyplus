![jellyplus](https://raw.githubusercontent.com/NsCRio/jellyplus/refs/heads/main/src/img/banner-light.png)
# Jellyplus (Beta)
## Open Source Streaming Media Center
### Jellyfin Media Center with Addons

---

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
  jellyplys:
    image: nscrio/jellyplus:latest
    container_name: jellyplus
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

### Hardware Acceleration Enhancements

This section lists the enhancements we have made for hardware acceleration in this image specifically.

#### OpenMAX (Raspberry Pi)

Hardware acceleration users for Raspberry Pi MMAL/OpenMAX will need to mount their `/dev/vcsm` and `/dev/vchiq` video devices inside of the container and their system OpenMax libs by passing the following options when running or creating the container:

```
--device=/dev/vcsm:/dev/vcsm
--device=/dev/vchiq:/dev/vchiq
-v /opt/vc/lib:/opt/vc/lib
```

#### V4L2 (Raspberry Pi)

Hardware acceleration users for Raspberry Pi V4L2 will need to mount their `/dev/video1X` devices inside of the container by passing the following options when running or creating the container:

```
--device=/dev/video10:/dev/video10
--device=/dev/video11:/dev/video11
--device=/dev/video12:/dev/video12
```

### Hardware Acceleration

Many desktop applications need access to a GPU to function properly and even some Desktop Environments have compositor effects that will not function without a GPU. However this is not a hard requirement and all base images will function without a video device mounted into the container.

#### Intel/ATI/AMD

To leverage hardware acceleration you will need to mount /dev/dri video device inside of the container.

```text
--device=/dev/dri:/dev/dri
```

We will automatically ensure the abc user inside of the container has the proper permissions to access this device.

#### Arm Devices

Best effort is made to install tools to allow mounting in /dev/dri on Arm devices. In most cases if /dev/dri exists on the host it should just work. If running a Raspberry Pi 4 be sure to enable `dtoverlay=vc4-fkms-v3d` in your usercfg.txt.

## Application Setup

Webui can be found at `http://<your-ip>:8096`

More information can be found on the official documentation [here](https://jellyfin.org/docs/general/quick-start.html).

