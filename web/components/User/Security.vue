<i18n src="../../translation/frontend/User.json"></i18n>
<template>
    <div class="security" align="left">
        <md-card>
            <md-card-header>
                <div class="md-title">{{ $t('security-settings') }}</div>
            </md-card-header>

            <md-card-content>
                <md-progress-bar md-mode="indeterminate" v-if="sending"/>
                    <p align="left">
                        <span class="md-caption">
                            {{ $t('notice-title') }}<br/>
                            {{ $t('notice-1') }}<br/>
                            {{ $t('notice-2') }}
                        </span>
                    </p>
                <md-field>
                    <label>{{ $t('password-current') }}</label>
                    <md-input type="password" name="password" id="password" autocomplete="password"
                              v-model="form.password"/>
                </md-field>
            </md-card-content>

            <md-card-actions>
                <md-button type="submit" class="md-primary" :disabled="sending" @click="submit">{{ $t('submit') }}</md-button>
            </md-card-actions>
        </md-card>

        <md-card>
            <md-card-header>
                <div class="md-title">{{ $t('change-password') }}</div>
            </md-card-header>
            <md-card-content>
                <md-field>
                    <label>{{ $t('password-new') }}</label>
                    <md-input type="password" name="newPassword" id="newPassword" v-model="form.newPassword"/>
                </md-field>
                <md-field>
                    <label>{{ $t('password-repeat') }}</label>
                    <md-input type="password" name="repeatPassword" id="repeatPassword" v-model="form.repeatPassword"/>
                </md-field>
                <md-checkbox v-model="form.clean">{{ $t('clean-associate') }}</md-checkbox>
            </md-card-content>
        </md-card>

        <md-card v-if="!email">
            <md-card-header>
                <div class="md-title">{{ $t('bind-email') }}</div>
            </md-card-header>
            <md-card-content>
                <md-field>
                    <label>{{ $t('email-new') }}</label>
                    <md-input name="newEmail" id="newEmail" v-model="form.newEmail"/>
                </md-field>
                <md-field v-if="!form.unbindEmail">
                    <label>{{ $t('code') }}</label>
                    <md-input name="emailCode" id="emailCode" v-model="form.emailCode"/>
                </md-field>
            </md-card-content>
            <md-card-actions>
                <md-button class="md-primary" @click="sendEmail">{{ $t("send") }}</md-button>
            </md-card-actions>
        </md-card>

<!--        <md-card>-->
<!--            <md-card-header>-->
<!--                <div class="md-title">{{ $t('change-phone' )}}</div>-->
<!--            </md-card-header>-->
<!--            <md-card-content>-->
<!--                <md-checkbox v-model="form.unbindPhone" v-if="phone !== $t('not-binded') && email !== $t('not-binded')">{{ $t('only-unbind') }}-->
<!--                </md-checkbox>-->
<!--                <md-field>-->
<!--                    <label>{{ $t('phone-current') }}</label>-->
<!--                    <md-input name="phone" id="phone" autocomplete="phone" v-model="phone" disabled/>-->
<!--                </md-field>-->
<!--                <md-field v-if="!form.unbindPhone">-->
<!--                    <label>{{ $t('phone-new') }}</label>-->
<!--                    <md-input name="newPhone" id="newPhone" v-model="form.newPhone"/>-->
<!--                </md-field>-->
<!--                <md-field v-if="!form.unbindPhone">-->
<!--                    <label>{{ $t('code') }}</label>-->
<!--                    <md-input name="phoneCode" id="phoneCode" v-model="form.phoneCode"/>-->
<!--                </md-field>-->
<!--            </md-card-content>-->
<!--            <md-card-actions>-->
<!--                <md-button class="md-primary" @click="sendSMS()">{{ $t("send") }}</md-button>-->
<!--            </md-card-actions>-->
<!--        </md-card>-->

        <md-dialog-alert
                :md-active.sync="incomplete"
                :md-content="$t('profile-incomplete')"
                md-confirm-text="OK" />
    </div>
</template>

<script>
    export default {
        name: "Security",
        props: ["isAdmin", "isLoggedIn", "isVerified", "gResponse"],
        data: () => ({
            sending: false,
            task: "",
            email: "",
            phone: "",
            form: {},
            incomplete: false
        }),
        created: function () {
            this.resetForm()
        },
        mounted: function () {
            this.$emit("changeTitle", this.$t("security-title"))
            this.incomplete = (this.$route.query.reason === 'incomplete')
        },
        methods: {
            resetForm() {
                this.axios.get("/user/current").then((response) => {
                    this.email = response.data["data"]["email"]
                    this.phone = response.data["data"]["phone"]
                    if (this.phone === null)
                        this.phone = this.$t('not-binded')
                }).catch((error) => {
                    this.$router.push('/user/login')
                })
                this.form = {
                    password: null,
                    newPassword: null,
                    repeatPassword: null,
                    newEmail: null,
                    phoneCode: null,
                    emailCode: null,
                    newPhone: null,
                    unbindPhone: false
                }
            },
            submit() {
                if (this.form.newPassword !== this.form.repeatPassword) {
                    this.showMsg(this.$t("password-mismatch"))
                    return
                }
                this.axios.post("/user/change", this.form).then((response) => {
                    if (response.data["code"] === 200) {
                        this.showMsg(this.$t("operation-succeeded"))
                        this.resetForm()
                    } else {
                        if (response.data["data"])
                            this.showMsg(response.data["data"])
                        else
                            this.showMsg(this.$t("password-only-incorrect"))
                    }
                }).catch((error) => {
                    this.$emit("generalError",error)
                })
            },
            sendEmail() {
                this.task = "email"
                this.send()
            },
            sendSMS() {
                this.task = "sms"
                this.send()
            },
            send() {
                switch (this.task) {
                    case "email":
                        this.axios.post("/user/code", {
                            "type": 3,
                            "email": this.form.newEmail
                        }).then((response) => {
                            if (response.data["code"] === 200) {
                                this.showMsg(this.$t("send-succeeded"))
                            } else {
                                this.showMsg(response.data["data"])
                            }
                        }).catch((error) => {
                            this.$emit("generalError",error)
                        })
                        break
                    case "sms":
                        this.axios.post("/user/code", {
                            "type": 3,
                            "phone": this.form.newPhone,
                            "captcha": grecaptcha.getResponse()
                        }).then((response) => {
                            if (response.data["code"] === 200) {
                                this.showMsg(this.$t("send-succeeded"))
                            } else {
                                this.showMsg(response.data["data"])
                            }
                        }).catch((error) => {
                            this.$emit("generalError",error)
                        })

                }
                this.task = ""
            },
            showMsg(msg) {
                this.$emit("showMsg", msg)
            }
        }
    }
</script>

<style scoped>
    .md-card {
        margin:10px;
    }
</style>