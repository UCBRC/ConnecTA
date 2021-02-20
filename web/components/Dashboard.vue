<i18n src="../translation/frontend/Homepage.json"></i18n>
<template>
    <div>
        <md-card v-if="photos.length > 0 && webpSupported">
            <md-card-header v-if="title">
                <div class="md-title">{{title}}</div>
            </md-card-header>
            <md-card-content>
                <el-carousel height="300px" type="card">
                    <el-carousel-item v-for="photo in photos" :key="photo">
                        <a :href="photo.url"><img class="slides" v-bind:src="photo.src"></a>
                    </el-carousel-item>
                </el-carousel>
                <div>
                    <span class="md-caption">{{$t("click-to-view-more")}}</span>
                </div>
            </md-card-content>


        </md-card>
        <md-card style="text-align:left;">
            <md-card-content>
                <markdown v-if="loaded" :markdown="announcement"></markdown>
            </md-card-content>
        </md-card>
        <!--
        <md-card>
            <md-card-header>
                <div class="md-title">精选功能</div>
            </md-card-header>
            <md-card-content>
                <div class="md-layout md-gutter md-alignment-center">
                    <div class="md-layout-item md-medium-size-25 md-small-size-50 md-xsmall-size-100">
                        <md-empty-state
                                md-icon="pool"
                                md-description="新一代学习系统">
                            <md-button class="md-primary md-raised" href="https://water.nfls.io">划水</md-button>
                        </md-empty-state>
                    </div>
                    <div class="md-layout-item md-medium-size-25 md-small-size-50 md-xsmall-size-100">
                        <md-empty-state
                                md-icon="photo_library"
                                md-description="表情包的发源地">
                            <md-button class="md-primary md-raised" to="/media/gallery">相册</md-button>
                        </md-empty-state>
                    </div>
                    <div class="md-layout-item md-medium-size-25 md-small-size-50 md-xsmall-size-100">
                        <md-empty-state
                                md-icon="forum"
                                md-description="荒无人烟的地方">
                            <md-button class="md-primary md-raised" href="https://forum.nfls.io">论坛</md-button>
                        </md-empty-state>
                    </div>
                    <div class="md-layout-item md-medium-size-25 md-small-size-50 md-xsmall-size-100">
                        <md-empty-state
                                md-icon="info"
                                md-description="校友们都在干啥">
                            <md-button class="md-primary md-raised" to="/alumni/directory">校友</md-button>
                        </md-empty-state>
                    </div>
                </div>
            </md-card-content>
        </md-card>
        -->
    </div>
</template>

<script>
    import Markdown from "./Components/Markdown"

    export default {
        name: "Dashboard",
        components: {
            Markdown,
        },
        props: ["isAdmin", "isLoggedIn", "isVerified", "webpSupported"],
        data: () => ({
            announcement: null,
            loaded: false,
            photos: [],
            title: null
        }),
        mounted: function () {
            this.$emit("changeTitle", this.$t("title"))
            this.axios.get("/message/announcement").then((response) => {
                this.announcement = response.data["data"]
                this.loaded = true
            })
            this.axios.get("/index/photos").then((response)=>{
                let photos = response.data["data"]
                if(Array.isArray(photos)) {
                    this.photos = photos
                } else {
                    this.loadImages(response.data["data"]["id"])
                }
            })
        },
        methods: {
            loadImages(id) {
                this.axios.get("/media/gallery/detail", {
                    params: {
                        id: id
                    }
                }).then((response) => {
                    this.photos = this.shuffle(response.data["data"]["photos"].filter(item => item.osrc != null).map((object)=>{
                        return {
                            "src": "/storage/photos/hd/" + object.src,
                            "url": "/#/media/gallery/" + id
                        }
                    })).slice(0,8)
                    this.title = response.data["data"]["title"]
                }).catch((error) => {
                    this.$emit("generalError",error)
                })
            },
            shuffle(array) {
                let currentIndex = array.length, temporaryValue, randomIndex;

                // While there remain elements to shuffle...
                while (0 !== currentIndex) {

                    // Pick a remaining element...
                    randomIndex = Math.floor(Math.random() * currentIndex);
                    currentIndex -= 1;

                    // And swap it with the current element.
                    temporaryValue = array[currentIndex];
                    array[currentIndex] = array[randomIndex];
                    array[randomIndex] = temporaryValue;
                }

                return array;
            }

        }
    }
</script>

<style scoped lang="scss">
    .md-card {
        margin: 10px;
    }

    .slides {
        max-width: 100%;
        max-height: 100%;
        margin: auto;
        vertical-align: middle;
    }
</style>