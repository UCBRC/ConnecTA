<i18n src="../../translation/frontend/User.json"></i18n>
<template>
    <div align="left" v-infinite-scroll="loadMore">
        <span class="md-title" v-if="!verified">
            {{ $t('not-realname' )}}
        </span>
        <div>
            <md-card v-for="chat in list" :key="chat.id">
                <md-card-content>
                    <markdown :markdown="chat.content"></markdown>
                    <md-divider></md-divider>
                    <span class="md-caption">
                        <span v-if="chat.canReply">From:<span v-html="chat.sender.htmlUsername"></span></span>
                        <span v-else>To:<span v-html="chat.receiver.htmlUsername"></span></span>
                        @{{chat.time | moment("lll")}}
                </span>
                </md-card-content>
                <md-card-actions>
                    <md-button @click="reply(chat.sender.id)" v-if="chat.canReply">{{ $t('reply') }}</md-button>
                </md-card-actions>
            </md-card>
        </div>

        <md-dialog :md-active.sync="showDialog" :md-click-outside-to-close="false">
            <md-dialog-content>
                <md-field>
                    <label>{{ $t('receiver') }}</label>
                    <md-input v-model="receiver" :disabled="sending"></md-input>
                </md-field>
                <mavon-editor v-model="content" :toolbars="toobars" :subfield="false"></mavon-editor>
                <md-progress-bar md-mode="indeterminate" v-if="sending"/>
            </md-dialog-content>
            <md-dialog-actions>
                <md-button class="md-primary" @click="showDialog = false" :disabled="sending">{{ $t('cancel') }}</md-button>
                <md-button class="md-primary" @click="send" :disabled="sending">{{ $t('submit') }}</md-button>
            </md-dialog-actions>

        </md-dialog>
        <md-speed-dial class="md-bottom-right" v-if="verified">
            <md-speed-dial-target @click="reply(null)">
                <md-icon>create</md-icon>
            </md-speed-dial-target>
        </md-speed-dial>
    </div>
</template>

<script>
    import infiniteScroll from 'vue-infinite-scroll'
    import Markdown from "../Components/Markdown"

    export default {
        name: "Message",
        props: ["admin", "loggedIn", "verified", "gResponse"],
        components: {
            Markdown
        },
        directives: {infiniteScroll},
        data: () => ({
            page: 1,
            content: "",
            receiver: null,
            list: [],
            active: null,
            showDialog: false,
            sending: false,
            toobars: {
                bold: true, // 粗体
                italic: true, // 斜体
                header: true, // 标题
                underline: true, // 下划线
                strikethrough: true, // 中划线
                mark: true, // 标记
                superscript: true, // 上角标
                subscript: true, // 下角标
                quote: true, // 引用
                ol: true, // 有序列表
                ul: true, // 无序列表
                link: true, // 链接
                help: true, // 帮助
            }
        }),
        mounted: function () {
            this.load()
            this.$moment.locale(this.$i18n.locale)
            this.$emit("changeTitle", this.$t("message-title"))
            if (this.$route.params["id"] != null) {
                this.receiver = this.$route.params["id"]
                this.showDialog = true
            }
        },
        methods: {
            load() {
                this.axios.get("/chat/list", {
                    params: {
                        page: this.page
                    }
                }).then((response) => {
                    if (this.page === 1)
                        this.list = response.data["data"]
                    else
                        this.list = this.list.concat(response.data["data"])
                }).catch((error) => {
                    this.$emit("generalError",error)
                })

            },
            send() {
                this.sending = true
                this.axios.post("/chat/send", {
                    id: this.receiver,
                    content: this.content,
                }).then((response) => {
                    if (response.data["code"] !== 200) {
                        this.$emit("showMsg", response.data["data"])
                    } else {
                        this.$emit("showMsg", this.$t('send-succeeded'))
                        this.receiver = null
                        this.content = ""
                        this.showDialog = false
                        this.page = 1
                        this.load()
                    }
                    this.sending = false
                }).catch((error) => {
                    console.error(error)
                    this.sending = false
                    this.$emit("showMsg", this.$t("send-failed"))
                })
            },
            reply(id) {
                this.showDialog = true
                this.content = ""
                this.receiver = id
            },
            loadMore() {
                this.page++
                this.load()
            }
        }
    }
</script>

<style scoped>
    .md-card {
        margin: 10px;
    }

    .md-dialog {
        min-width: 500px;
        width: 80%;
    }

    .md-progress-bar {
        position: absolute;
        top: 0;
        right: 0;
        left: 0;
    }
</style>