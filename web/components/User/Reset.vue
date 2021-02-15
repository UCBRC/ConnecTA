<i18n src="../../translation/frontend/User.json"></i18n>
<template>
    <div class="login">
        <form novalidate class="md-layout-row md-gutter" @submit.prevent="reset">
            <md-card class="md-flex-50 md-flex-small-100 login-card">
                <md-card-header>
                    <md-card-header-text>
                        <div class="md-title">{{ $t('reset-title') }}</div>
                        <div class="md-subtitle">{{ $t('reset-title') }}</div>
                    </md-card-header-text>
                </md-card-header>

                <md-card-content>
                    <div class="md-layout-row md-layout-wrap md-gutter">
                        <!--
                        <div class="md-flex md-flex-small-100">
                            <md-radio v-model="way" value="phone">{{ $t("phone") }}</md-radio>
                            <md-radio v-model="way" value="email">{{ $t("email") }}</md-radio>
                        </div>
                        -->
                        <div v-if="way == 'phone'">
                            <div class="md-flex md-flex-small-100">
                                <md-field :class="getValidationClass('phone')">
                                    <label>{{ $t('phone') }}</label>
                                    <md-input name="phone" id="phone" autocomplete="phone" v-model="form.phone"
                                              :disabled="sending"/>
                                    <span class="md-error" v-if="$v.form.phone && !$v.form.phone.required">{{ $t('required') }}</span>
                                    <span class="md-error" v-else-if="$v.form.phone && !$v.form.phone.numeric">{{ $t('phone-invalid') }}</span>
                                </md-field>
                            </div>
                            <div class="md-flex md-flex-small-100">
                                <md-button class="md-raised" @click="sendSMS">{{$t('send')}}</md-button>
                            </div>
                        </div>
                        <div v-else-if="way == 'email'">
                            <div class="md-flex" >
                                <md-field :class="getValidationClass('email')">
                                    <label>{{ $t('email') }}</label>
                                    <md-input name="email" id="email" autocomplete="email" v-model="form.email"
                                              :disabled="sending"/>
                                    <span class="md-error" v-if="$v.form.email && !$v.form.email.required">{{ $t('required') }}</span>
                                    <span class="md-error" v-else-if="$v.form.email && !$v.form.email.email"></span>
                                </md-field>
                            </div>
                            <div class="md-flex md-flex-small-100">
                                <md-button class="md-raised" @click="sendEmail">{{$t('send')}}</md-button>
                            </div>
                        </div>
                        <div class="md-flex md-flex-small-100">
                            <md-field :class="getValidationClass('code')">
                                <label>{{ $t('code') }}</label>
                                <md-input name="code" id="code" autocomplete="code" v-model="form.code"
                                          :disabled="sending"/>
                                <span class="md-error" v-if="$v.form.code && !$v.form.code.required">{{ $t('required') }}</span>
                            </md-field>
                        </div>
                        <div class="md-flex md-flex-small-100">
                            <md-field :class="getValidationClass('password')">
                                <label>{{ $t('password') }}</label>
                                <md-input name="password" id="password" autocomplete="password" v-model="form.password"
                                          :disabled="sending" type="password"/>
                                <span class="md-error" v-if="$v.form.password && !$v.form.password.required">{{ $t('required') }}</span>
                                <span class="md-error" v-else-if="$v.form.code && !$v.form.password.maxLength">{{ $t('password-too-long') }}</span>
                                <span class="md-error" v-else-if="$v.form.code && !$v.form.password.minLength">{{ $t('password-too-short') }}</span>
                            </md-field>
                        </div>

                        <div class="md-flex md-flex-small-100">
                            <md-field :class="getValidationClass('repass')">
                                <label>{{ $t('password-repeat') }}</label>
                                <md-input name="repass" id="repass" autocomplete="repass" v-model="form.repass"
                                          :disabled="sending" type="password"/>
                                <span class="md-error" v-if="$v.form.repass && !$v.form.repass.required">{{ $t('required') }}</span>
                                <span class="md-error" v-else-if="passwordMismatch">{{ $t('password-mismatch') }}</span>
                            </md-field>
                        </div>

                        <div class="md-flex md-flex-small-100">
                            <md-button type="submit" class="md-raised md-primary" :disabled="sending">
                                {{ $t('reset') }}
                            </md-button>
                        </div>
                    </div>

                </md-card-content>
                <md-divider></md-divider>
                <md-card-actions md-alignment="left">
                    <md-button to="/user/login">{{ $t('login') }}</md-button>
                </md-card-actions>

                <md-progress-bar md-mode="indeterminate" v-if="sending"/>
            </md-card>

            <md-dialog-alert
                    :md-active.sync="warning"
                    :md-content="text"
                    md-confirm-text="OK"/>
        </form>
    </div>

</template>

<script>
    import {validationMixin} from 'vuelidate'
    import {
        required,
        email,
        minLength,
        maxLength,
        numeric,
        sameAs
    } from 'vuelidate/lib/validators'

    export default {
        name: 'Reset',
        mixins: [validationMixin],
        props: ['gResponse'],
        data: () => ({
            form: {
                phone: null,
                email: null,
                code: null,
                password: null,
                repass: null,
                privacy: false,
                tos: false
            },
            validateItems: {
                phone: {},
                email: {},
                code: {},
                password: {},
                repass: {}
            },
            sending: false,
            passwordMismatch: false,
            text: '',
            warning: false,
            way: 'email'
        }),
        validations() {
            return {
                form: this.validateItems
            }
        },
        methods: {
            getValidationClass(fieldName) {
                const field = this.$v.form[fieldName]
                if (field) {
                    return {
                        'md-invalid': field.$invalid && field.$dirty
                    }
                }
            },
            send() {
                this.sending = true
                this.axios.post("/user/code", {
                    "type": 2,
                    "phone": this.form.phone,
                    "email": this.form.email
                }).then((response) => {
                    this.sending = false
                    if (response.data["code"] === 200) {
                        this.showMsg(this.$t("send-succeeded"))
                    } else {
                        this.showMsg(response.data["data"])
                    }
                }).catch((error) => {
                    this.sending = false
                    this.$emit("generalError",error)
                })
            },
            reset() {
                this.validateItems = {
                    code: {
                        required
                    },
                    password: {
                        required,
                        minLength: minLength(8),
                        maxLength: maxLength(20)
                    },
                    repass: {
                        required
                    }
                }
                this.$v.$touch()
                if (!this.$v.$invalid) {
                    if (this.form.password !== this.form.repass) {
                        this.text = this.$t("password-mismatch")
                        this.warning = true
                    } else {
                        this.sending = true
                        this.axios.post("/user/reset", this.form).then((response) => {
                            if (response.data["code"] === 200) {
                                this.showMsg(this.$t("resetted"))
                                window.setTimeout(() => {
                                    this.$router.push("/user/login");
                                }, 3000)
                            } else {
                                this.showMsg(response.data["data"])
                                this.sending = false
                            }
                        }).catch((error) => {
                            this.sending = false
                            this.$emit("generalError",error)
                        })
                    }
                }
            },
            sendSMS() {
                this.validateItems = {
                    phone: {
                        required,
                        numeric
                    }
                }
                this.$v.$touch()
                if (!this.$v.$invalid) {
                    this.form.email = null
                    this.send()
                }
            },
            sendEmail() {
                this.validateItems = {
                    email: {
                        required,
                        email
                    }
                }
                this.$v.$touch()
                if (!this.$v.$invalid) {
                    this.form.phone = null
                    this.send()
                }
            },
            showMsg(msg) {
                this.$emit("showMsg", msg)
            }
        },
        mounted: function () {
            this.$emit("changeTitle", this.$t("reset-title"))
        }
    }
</script>

<style lang="scss" scoped>
    .md-progress-bar {
        position: absolute;
        top: 0;
        right: 0;
        left: 0;
    }

    .login {
        margin-top: 80px;
        margin-left: auto;
        margin-right: auto;
        width: 70%;
        height: 100%;
        min-width: 260px;
        max-width: 500px;
    }
</style>
