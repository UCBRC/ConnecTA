<i18n src="../../translation/frontend/Alumni.json"></i18n>
<template>
    <div id="list">
        <md-card>
            <md-card-header>
                {{ $t('status') }}
            </md-card-header>
            <md-card-content>
                <p align="left">
                    <markdown v-if="header" :markdown="header"></markdown>
                </p>
                <md-divider></md-divider>
                <form>
                    <div>
                        <md-checkbox v-model="valid" class="md-primary" readonly disabled>{{ $t('verified-user') }}</md-checkbox>
                        <md-field v-if="valid">
                            <label>{{ $t('chineseName') }}</label>
                            <md-input name="chineseName" id="chineseName" v-model="chineseName" readonly/>
                        </md-field>
                        <md-field v-if="valid">
                            <label>{{ $t('englishName') }}</label>
                            <md-input name="englishName" id="englishName" v-model="englishName" readonly/>
                        </md-field>
                        <md-field v-if="valid">
                            <label>{{ $t('verified-time') }}</label>
                            <md-input name="submitTime" id="submitTime" v-model="submitTime" readonly/>
                        </md-field>
                        <md-field v-if="valid">
                            <label>{{ $t('expired-time') }}</label>
                            <md-input name="submitTime" id="submitTime" v-model="expireAt" readonly/>
                        </md-field>
                    </div>
                </form>
            </md-card-content>
            <md-divider></md-divider>
            <md-card-actions md-alignment="left">
                <md-button v-if="allowNew" @click="newForm()">{{ $t('new-form') }}
                </md-button>
                <md-button disabled v-else>
                    {{ $t('unsubmitted') }}
                </md-button>
            </md-card-actions>
        </md-card>
        <md-card>
            <md-card-header>
                {{ $t('my-record') }}
            </md-card-header>
            <md-card-content>
                <md-list>
                    <md-list-item v-for="auth in history" :key="auth.id" @click="click(auth.id)"><code>{{auth.readableText}}</code>
                    </md-list-item>
                </md-list>
            </md-card-content>
        </md-card>
    </div>
</template>

<script>
    import Markdown from "../Components/Markdown"
    export default {
        name: "Realname",
        data: () => ({
            history: [],
            allowNew: false,
            valid: false,
            chineseName: "",
            englishName: "",
            submitTime: "",
            expireAt: "",
            header: null
        }),
        components: {
            Markdown
        },
        mounted: function () {
            this.axios.get("/alumni/header").then((response) => {
                this.header = response.data["data"]
            }).catch((error) => {
                this.$emit("generalError", error)
            })
            this.$moment.locale(this.$i18n.locale)
            this.loadData()
            this.loadStatus()
            this.$emit('changeTitle', this.$t('title-auth'))
        },
        methods: {
            loadData() {
                this.axios.get("/alumni/info").then((response) => {
                    var self = this
                    this.history = response.data["data"].map(function (val) {
                        val["readableText"] = self.$t(self.getStatus(val.status))
                        val["readableText"] += " " + val.id
                        if (val.submitTime) {
                            val["readableText"] += " " + self.$t('submit-time')  + self.$moment(val.submitTime).format("lll") + " "
                        }
                        return val
                    })
                    this.allowNew = (this.history.filter(function (val) {
                        return val.status <= 1
                    }).length == 0)
                }).catch((error) => {
                    console.error(error)
                    this.$router.push("/user/login")
                })
            },
            loadStatus() {
                this.axios.get("/alumni/current").then((response) => {
                    //var self = this
                    var data = response.data["data"]
                    var self = this
                    if (data) {
                        this.valid = true
                        this.chineseName = data["chineseName"]
                        this.englishName = data["englishName"]
                        this.submitTime = self.$moment(data["submitTime"]).format("lll")
                        if (data["expireAt"])
                            this.expireAt = self.$moment(data["expireAt"]).format("L")
                        else
                            this.expireAt = this.$t('un-metered')
                    } else {
                        this.valid = false
                    }
                }).catch((error) => {
                    console.error(error)
                    this.$router.push("/user/login")
                })
            },
            click(id) {
                this.$router.push("/alumni/auth/" + id);
            },
            getStatus(status) {
                return "status-" + status
            },
            newForm() {
                this.axios.post("alumni/new").then((response) => {
                    if(response.data["code"] === 200)
                        this.loadData()
                    else
                        this.$emit("showMsg",response.data["data"])
                }).catch((error) => {
                    this.$emit("generalError",error)
                })
            }

        }
    }
</script>

<style scoped>
    .md-checkbox {
        display: flex;
    }

    .md-card {
        margin: 10px;
    }
</style>