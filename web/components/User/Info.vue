<i18n src="../../translation/frontend/User.json"></i18n>
<template>
    <div align="left">
        <md-progress-bar md-mode="indeterminate" v-if="sending"/>
        <md-card  style="margin: 10px">
            <md-card-header>
                <span class="md-title">{{ $t('account-info') }}</span>
            </md-card-header>
            <md-card-content>
                <md-list class="md-douple-line">
                    <md-list-item>
                        <md-avatar @click.native="avatarDialog = !avatarDialog">
                            <img :src="avatar" alt="People">
                        </md-avatar>
                        <div class="md-list-item-text">
                            <span>{{ $t('username') }}</span>
                            <span>{{info.username}}</span>
                        </div>
                        <md-button class="md-icon-button md-list-action" @click="usernameDialog = !usernameDialog" :disabled="sending">
                            <md-icon>edit</md-icon>
                        </md-button>
                    </md-list-item>
                    <md-divider></md-divider>
                    <md-list-item>
                        <div class="md-list-item-text">
                            <span>ID</span>
                            <span>{{info.id}}</span>
                        </div>
                    </md-list-item>
                    <md-divider></md-divider>
<!--                    <md-list-item>-->
<!--                        <div class="md-list-item-text">-->
<!--                            <span>{{ $t('cas-hours') }}</span>-->
<!--                            <span>{{info.point}}</span>-->
<!--                        </div>-->
<!--                        <md-tooltip md-direction="bottom">{{ $t('cas-prompt') }}</md-tooltip>-->
<!--                    </md-list-item>-->
                    <md-divider></md-divider>
                    <md-list-item>
                        <div class="md-list-item-text">
                            <span>{{ $t('jointime') }}</span>
                            <span>{{info.joinTime | moment("lll")}}</span>
                        </div>
                    </md-list-item>
                    <md-divider></md-divider>
                    <md-list-item>
                        <div class="md-list-item-text">
                            <span>{{ $t('email') }}</span>
                            <span>{{info.email}}</span>
                        </div>
                        <md-button class="md-icon-button md-list-action" to="/user/security" :disabled="sending">
                            <md-icon>edit</md-icon>
                        </md-button>
                    </md-list-item>
                    <md-divider></md-divider>
<!--                    <md-list-item>-->
<!--                        <div class="md-list-item-text">-->
<!--                            <span>{{ $t('phone') }}</span>-->
<!--                            <span>{{info.phone}}</span>-->
<!--                        </div>-->
<!--                        <md-button class="md-icon-button md-list-action" to="/user/security" :disabled="sending">-->
<!--                            <md-icon>edit</md-icon>-->
<!--                        </md-button>-->
<!--                    </md-list-item>-->
                </md-list>
            </md-card-content>
        </md-card>

<!--        <md-card  style="margin: 10px">-->
<!--            <md-card-header>-->
<!--                <span class="md-title">隐私设置</span>-->
<!--            </md-card-header>-->
<!--            <md-card-content>-->
<!--                <span class="md-caption">-->
<!--                    反爬虫保护可以防止您账户信息被机器人或是网络爬虫自动抓取。<br/>-->
<!--                    启用该功能后，用户无法通过修改地址进入您的用户信息页。用户只能通过点击姓名标签进入，即只能通过校友录搜索或是私信页面进入。<br/>-->
<!--                </span>-->
<!--                <md-checkbox v-model="antiSpider">启用反爬虫保护</md-checkbox>-->
<!--                <br/>-->
<!--                <md-field>-->
<!--                    <label>将我的实名认证完整信息展示给</label>-->
<!--                    <md-select v-model="general" name="general" id="general">-->
<!--                        <md-option v-for="(level, index) in privacyLevel" :key="index" :value="index">{{ level }}</md-option>-->
<!--                    </md-select>-->
<!--                </md-field>-->
<!--            </md-card-content>-->
<!--            <md-card-actions>-->
<!--                <md-button class="md-raised md-primary" @click="submit">提交</md-button>-->
<!--            </md-card-actions>-->
<!--        </md-card>-->
        <md-dialog-prompt
                :md-active.sync="usernameDialog"
                v-model="username"
                :md-input-placeholder="$t('username')"
                md-input-maxlength="16"
                :md-title="$t('edit-username')"
                :md-confirm-text="$t('submit')"
                :md-cancel-text="$t('cancel')"
                @md-confirm="confirm"/>
        <md-dialog-prompt/>
        <md-dialog :md-active.sync="avatarDialog">
            <md-dialog-title>{{ $t('edit-avatar') }}</md-dialog-title>
            <md-dialog-content>
                <form @submit.prevent="changeAvatar">
                    <md-field>
                        <label>{{ $t('avatar') }}</label>
                        <md-file name="avatar" id="avatar" accept="image/jpeg" @md-change="change"/>
                    </md-field>
                </form>
            </md-dialog-content>
            <md-dialog-actions>
                <md-button class="md-primary" @click="avatarDialog = !avatarDialog">{{ $t('cancel') }}</md-button>
                <md-button type="submit" class="md-primary" @click="changeAvatar" :disabled="sending">{{ $t('submit') }}</md-button>
            </md-dialog-actions>
        </md-dialog>
    </div>
</template>

<script>
    export default {
        name: "Info",
        props: ["avatar"],
        data: () => ({
            avatarPath: null,
            info: [],
            usernameDialog: false,
            avatarDialog: false,
            username: null,
            sending: false,
            privacyLevel: {
                1: "仅同校同学（所有已实名用户）",
                2: "仅同届同学",
                3: "仅自己"
            },
            general: 0,
            antiSpider: true
        }),
        mounted: function () {
            this.$moment.locale(this.$i18n.locale)
            this.$emit("changeTitle", this.$t("info-title"))
            this.load()
        },
        methods: {
            load() {
                this.axios.get('/user/current').then((response) => {
                    if (response.data['code'] === 200) {
                        this.info = response.data["data"]
                        if (this.info.email === null)
                            this.info.email = this.$t("not-binded")
                        if (this.info.phone === null)
                            this.info.phone = this.$t("not-binded")
                    } else {
                        this.$router.push("/user/login")
                    }
                }).catch((error) => {
                    console.error(error)
                    this.$router.push("/user/login")
                })
                this.axios.get("/user/privacy").then((response) => {
                    this.antiSpider = response.data["data"].antiSpider
                    this.general = response.data["data"].privacy
                })
            }, changeAvatar() {
                this.sending = true
                if (this.avatarPath === null || this.avatarPath.length !== 1) {
                    this.avatarDialog = false
                    return
                }
                var formData = new FormData();
                formData.append('photo', this.avatarPath[0]);
                this.axios.post("/user/avatar", formData).then((response) => {
                    this.sending = false
                    this.avatarDialog = false
                    this.$emit("reload")
                }).catch((error) => {
                    this.sending = false
                    this.$emit("generalError",error)
                })
            }, change(file) {
                this.avatarPath = file
            }, confirm(username) {
                this.sending = true
                this.axios.post("/user/rename", {
                    username: username
                }).then((response) => {
                    this.sending = false
                    if (response.data["code"] !== 200) {
                        this.$emit("showMsg", response.data["data"])
                    }
                    this.$emit("reload")
                    this.load()
                }).catch((error) => {
                    this.sending = false
                    this.$emit("generalError",error)
                })
            }, submit() {
                this.sending = true
                this.axios.post("/user/privacy", {
                    privacy: this.general,
                    antiSpider: this.antiSpider
                }).then((response) => {
                    this.sending = false
                    this.antiSpider = response.data["data"].antiSpider
                    this.privacy = response.data["data"].privacy
                }).catch((error) => {
                    this.sending = false
                    this.$emit("generalError",error)
                })
            }
        }
    }
</script>

<style scoped>
</style>