<i18n src="../../translation/frontend/Alumni.json"></i18n>
<template>
    <div>
        <md-card>
            <md-card-header>
                <div class="md-title">成员查询</div>
            </md-card-header>

            <md-card-content>
                <md-field>
                    <label>综合</label>
                    <md-input v-model="name"></md-input>
                </md-field>
<!--                <md-field>-->
<!--                    <label>毕业年份（可选）</label>-->
<!--                    <md-input v-model="registration"></md-input>-->
<!--                </md-field>-->
<!--                <md-field>-->
<!--                    <label>入学班级（可选）</label>-->
<!--                    <md-input v-model="claz"></md-input>-->
<!--                </md-field>-->

            </md-card-content>

            <md-card-actions>
                <md-button class="md-raised md-primary" @click="search">检索</md-button>
            </md-card-actions>
        </md-card>
        <md-card style="margin-top: 50px;">
            <md-card-content>
                <md-list>
                    <md-list-item v-for="student in list" :key="student.id">
                        <md-avatar>
                            <img :src="'/avatar/' + student.id + '.png'" alt="Avatar">
                        </md-avatar>
                        <div class="md-list-item-text">
                            <span v-html="student.htmlUsername"></span>
                        </div>
                        <md-button class="md-icon-button md-list-action" @click="info(student.id)"><md-icon>info</md-icon></md-button>
                    </md-list-item>
                </md-list>
            </md-card-content>
        </md-card>
    </div>

</template>

<script>
    export default {
        name: "Directory",
        props: ['verified'],
        data: () => ({
            name: "",
            registration: "",
            claz: "",
            list: []
        }),
        mounted: function () {
            this.$emit("changeTitle", this.$t('title-directory'))
            this.load()
            if(!this.verified) {
                this.$emit("showMsg", "请先加入 ConneTA！")
                this.$router.push("/alumni/auth")
            }
        },
        methods: {
            load() {
            },
            search() {
                // grecaptcha.execute()
              this.ct()
            },
            ct() {
                let registration = this.registration
                if(registration === "")
                    registration = null
                let claz = this.claz
                if(claz === "")
                    claz = null
                this.axios.post("alumni/directory/query", {
                    name: this.name,
                    registration: registration,
                    class: claz,
                    // captcha: grecaptcha.getResponse()
                }).then((response) => {
                    if (response.data["code"] === 200)
                        this.list = response.data["data"]
                    else
                        this.$emit("showMsg", response.data["data"])
                    // grecaptcha.reset()
                }).catch((error) => {
                    this.$emit("generalError",error)
                    // grecaptcha.reset()
                })
            },
            info(id) {
                let url = this.$router.resolve("/user/page/" + id)
                window.open(url.href, "_blank");
            }
        },
        watch: {
            // gResponse() {
            //     this.ct()
            // }
        }
    }
</script>

<style scoped>

</style>