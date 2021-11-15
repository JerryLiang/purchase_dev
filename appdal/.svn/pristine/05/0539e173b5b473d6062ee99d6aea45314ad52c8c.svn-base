#!/bin/bash
log='>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>开始构建服务:'${JOB_NAME}
dos2unix /root/.m2/dockerregistry && source /root/.m2/dockerregistry
CUR_DATETIME_STR=$(date "+%Y-%m-%d--%H-%M")
IMAGE_ID=${DOCKER_REGISTRY_URL}"/"${JOB_NAME}":"${CUR_DATETIME_STR}
log='<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<正在构建镜像'
docker build -t ${IMAGE_ID} .
log='<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<构建镜像成功'
log='>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>正在登录镜像仓库'
cat /root/.m2/dockerregistry-auth |  docker login ${DOCKER_REGISTRY_URL} --username ${DOCKER_REGISTRY_USERNAME} --password-stdin
log='<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<登录镜像仓库成功'
log='>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>正在推送镜像到镜像仓库'
docker push ${IMAGE_ID}
log='<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<推送镜像到镜像仓库成功'
log='>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>正在清理本地镜像'
docker rmi ${IMAGE_ID}
log='<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<清理本地镜像成功'
log='<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<完成构建服务:'${JOB_NAME}
log='请拷贝镜像id到下一环节,镜像id为:'
echo ${IMAGE_ID}