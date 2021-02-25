<i18n src="../../translation/frontend/School.json"></i18n>
<template>
    <div align="left">
        <div v-if="current && !loading">
            <md-card>
                <md-card-header>
                    <div class="md-title">{{ current.title }}</div>
                </md-card-header>
                <md-card-content>
                    <markdown :markdown="current.content"></markdown>
                </md-card-content>
            </md-card>
        </div>

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
            this.$emit('changeTitle', "活动")
            this.list()
        },
        data: () => ({
            id: "",
            events: [],
            current: null,
            loading: true
        }),
        methods: {
            list() {
                this.axios.get("/event/list").then((response) => {
                    this.events = response.data["data"]
                    if(this.events.length > 0)
                        this.id = this.events[0].id
                    this.loading = false
                }).catch((error)=>{
                    this.$router.push("/user/login")
                    console.log(error)
                })
            },
            load() {
                this.axios.get("/event/detail?id="+this.id).then((response) => {
                    this.current = response.data["data"]
                }).catch((error)=>{
                    this.$emit("generalError", error)
                })
            },
            submit() {
                this.axios.post("/school/join", {
                    id: this.id,
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
            }
        }
    }
</script>

<style scoped>
</style>