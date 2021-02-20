<i18n src="../../translation/frontend/School.json"></i18n>
<template>
    <div align="left">
        <div v-if="!loading">
            <md-field>
                <label>请选择</label>
                <md-select v-model="id" @md-selected="load">
                    <md-option v-for="vote in votes" :key="vote.id" :value="vote.id">{{ vote.title }}</md-option>
                </md-select>
            </md-field>
        </div>
        <div v-if="current && !loading">

            <md-card>
                <md-card-header>
                    <div class="md-title">{{ current.title }}</div>
                </md-card-header>
                <md-card-content>
                    <markdown :markdown="current.content"></markdown>
                    <md-divider></md-divider>
                    <span v-if="code === 401 && !current.enabled" class="md-caption">投票已结束，或尚未开始</span>
                    <span v-if="code === 401 && current.enabled" class="md-caption">您所在的用户组无法投票</span>
                    <span v-if="code === 403" class="md-caption">您已经投过票了</span>
                    <form>
                        <div v-for="(item, itemKey) in current.options" :key="itemKey">
                            <span class="md-body-2"> 项目{{itemKey + 1}} ： {{ item.text }} </span>
                            <div>
                                <md-radio v-for="(option, key) in item.options" :key="key" v-model="choices[itemKey]" :value="key" :disabled="!current.enabled || code !== 400">{{ option }}</md-radio>
                            </div>
                        </div>
                    </form>
                </md-card-content>
                <md-card-actions>
                    <md-button class="md-raised md-primary" :disabled="!current.enabled || code !== 400" @click="confirm">提交</md-button>
                </md-card-actions>
            </md-card>
        </div>
        <md-dialog
                :md-active.sync="confirmation"
                md-title=""
                md-content=""
                md-confirm-text="确认"
                md-cancel-text="取消"
                @md-confirm="submit">
            <md-dialog-title>确认提交</md-dialog-title>
            <md-dialog-content>

                    提交后，您将无法修改您的选择。您确认要提交吗？
                    <md-field>
                        <label>密码</label>
                        <md-input v-model="password" type="password"></md-input>
                    </md-field>

            </md-dialog-content>
            <md-dialog-actions>
                <md-button @click="confirmation = false">取消</md-button>
                <md-button @click="submit">确认</md-button>
            </md-dialog-actions>
        </md-dialog>
        <md-dialog-alert
                :md-active.sync="error"
                :md-content="message"
                md-confirm-text="好的" />
        <md-dialog :md-active.sync="result">
            <md-dialog-title>投票成功</md-dialog-title>
            <div style="margin-left: 20px; margin-right: 20px;">
                <span class="md-body-2">
                您已投票成功。该查询码可用于查询自己的投票信息。请注意保护好该查询码，不要告诉他人。
            </span><br/><br/>
                <span class="md-body-1">{{ query }}</span>
            </div>
            <md-dialog-actions>
                <md-button class="md-primary" @click="result = false">关闭</md-button>
            </md-dialog-actions>
        </md-dialog>
    </div>
</template>

<script>
    import Markdown from "../Components/Markdown"

    export default {
        components: {
            Markdown
        },
        name: "Vote",
        mounted: function () {
            this.$emit('changeTitle', "投票")
            this.list()
        },
        data: () => ({
            id: "",
            votes: [],
            current: null,
            choices: [],
            confirmation: false,
            error: false,
            message: "",
            result: false,
            code: "",
            query: "",
            loading: true,
            password: ""
        }),
        methods: {
            list() {
                this.axios.get("/school/vote/list").then((response) => {
                    this.votes = response.data["data"]
                    if(this.votes.length > 0)
                        this.id = this.votes[0].id
                    this.loading = false
                }).catch((error)=>{
                    this.$router.push("/user/login")
                    console.log(error)
                })
            },
            load() {
                this.axios.get("/school/vote/detail?id="+this.id).then((response) => {
                    this.current = response.data["data"]
                    this.status()
                }).catch((error)=>{
                    this.$emit("generalError", error)
                })
            },
            confirm() {
                this.confirmation = true
            },
            submit() {
                this.confirmation = false
                this.axios.post("/school/vote/vote", {
                    id: this.id,
                    choices: this.choices,
                    password: this.password
                }).then((response) => {
                    if(response.data["code"] === 200){
                        this.result = true
                        this.query = response.data["data"].code
                        this.status()
                    }else{
                        this.message = response.data["data"]
                        this.error = true
                    }
                }).catch((error) => {
                    this.message = "网络错误，请联系管理员"
                    this.error = true
                    console.error(error)
                })
            },
            status() {
                this.axios.post("/school/vote/vote", {
                    id: this.id
                }).then((response) => {
                    this.code = response.data["code"]
                })
            }
        }
    }
</script>

<style scoped>
</style>