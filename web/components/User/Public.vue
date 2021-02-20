<i18n src="../../translation/frontend/Alumni.json"></i18n>
<template>
    <div>
        <div v-if="visible">
            <span class="md-title">{{info.username}} 的个人页面</span>
            <br/>
            <div v-if="alumni == null">
                <span class="md-caption">该用户尚未加入 ConnecTA。</span>
            </div>
            <div v-else>
                <md-list class="md-douple-line">
                    <md-list-item>
                        <div class="md-list-item-text">
                            <span class="md-caption">ID</span>
                            <span>{{info.id}}</span>
                        </div>
                    </md-list-item>
                    <md-divider></md-divider>
                    <md-list-item>
                        <div class="md-list-item-text">
                            <span class="md-caption">用户名</span>
                            <span>{{info.username}}</span>
                        </div>
                    </md-list-item>
                    <md-divider></md-divider>
                    <md-list-item>
                        <div class="md-list-item-text">
                            <span class="md-caption">中文名</span>
                            <span>{{alumni.chineseName}}</span>
                        </div>
                    </md-list-item>
                    <md-divider></md-divider>
                    <md-list-item>
                        <div class="md-list-item-text">
                            <span class="md-caption">英文名</span>
                            <span>{{alumni.englishName}}</span>
                        </div>
                    </md-list-item>
                  <md-divider></md-divider>
                  <md-list-item>
                    <div class="md-list-item-text">
                      <span class="md-caption">大学</span>
                      <span>{{alumni.university}}</span>
                    </div>
                  </md-list-item>

                </md-list>
            </div>
        </div>
        <div v-else>
            <span class="md-caption">该用户启用了反爬虫保护。</span>
        </div>
    </div>

</template>

<script>
    export default {
        name: "Public",
        data: () => ({
            visible: false,
            alumni: {},
            info: {},
            countries:[]
        }),
        mounted() {
            this.request()
        },
        methods: {
            request() {
                this.axios.get("/alumni/countries").then((response) => {
                    this.countries = response.data["data"]
                })
                this.axios.get("/user/page",{
                    params: {
                        id: this.$route.params["id"]
                    }
                }).then((response) => {
                    if(response.data["code"] === 200){
                        this.visible = true
                        this.alumni = response.data["data"]["alumni"]
                        this.info = response.data["data"]["info"]
                    } else {
                        this.visible = false
                    }
                })
            },
            visit() {
                this.$router.push("/user/message/" + this.info.id)
            },
            getSeniorSchool(school, registration, cla) {
                return this.$t("form-seniorSchool-" + school) + " " + registration + "届 " + cla + "班"
            },
            getJuniorSchool(school, registration, cla) {
                return this.$t("form-juniorSchool-" + school) + " " + registration + "届 " + cla + "班"
            },
            getGender(gender) {
                return this.$t('form-gender-'+gender)
            }
        }
    }
</script>

<style scoped>

</style>