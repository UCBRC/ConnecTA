<i18n src="../../translation/frontend/User.json"></i18n>
<template>
    <div class="login">
        <form novalidate class="md-layout-row md-gutter" @submit.prevent="validate">
            <md-card class="md-flex-50 md-flex-small-100 login-card">
                <md-card-header>
                    <md-card-header-text>
                        <div class="md-title"> {{ $t('login-title') }}</div>
                        <div class="md-subtitle">{{ $t('login-subtitle') }}</div>

                    </md-card-header-text>
                </md-card-header>

                <md-card-content>
                    <div class="md-layout-row md-layout-wrap md-gutter">
                        <div class="md-flex md-flex-small-100">
                            <md-field :class="getValidationClass('username')">
                                <label>{{ $t('login-username') }}</label>
                                <md-input name="username" id="username" autocomplete="username" v-model="form.username"
                                          :disabled="sending"/>
                                <span class="md-error" v-if="!$v.form.username.required">{{ $t('required') }}</span>
                            </md-field>
                        </div>
                        <div class="md-flex md-flex-small-100">
                            <md-field :class="getValidationClass('password')">
                                <label>{{ $t('password') }}</label>
                                <md-input name="password" id="password" autocomplete="password" v-model="form.password"
                                          :disabled="sending" type="password"/>
                                <span class="md-error" v-if="!$v.form.password.required">{{ $t('required') }}</span>
                            </md-field>
                        </div>
                        <div class="md-flex md-flex-small-100">
                            <md-checkbox v-model="form.remember" :disabled="sending" style="width:100%"
                                         class="md-primary">{{ $t('remember') }}
                            </md-checkbox>
                        </div>
                        <div class="md-flex md-flex-small-100">
                            <md-button type="submit" class="md-raised md-primary">{{ $t('login') }}</md-button>
                        </div>
                    </div>

                </md-card-content>
                <md-divider></md-divider>
                <md-card-actions md-alignment="left">
                    <md-button to="/user/register">{{ $t('register') }}</md-button>
                    <md-button to="/user/reset">{{ $t('reset') }}</md-button>
                </md-card-actions>

                <md-progress-bar md-mode="indeterminate" v-if="sending"/>
            </md-card>
        </form>
    </div>
</template>

<script>
    import {validationMixin} from 'vuelidate'
    import {
        required
    } from 'vuelidate/lib/validators'

    export default {
        name: 'Login',
        mixins: [validationMixin],
        props: ['gResponse', "isLoggedIn"],
        data: () => ({
            form: {
                username: null,
                remember: true,
                password: null
            },
            sending: false
        }),
        validations: {
            form: {
                username: {
                    required
                },
                password: {
                    required
                }
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
            validate() {
                this.$v.$touch()
                if (!this.$v.$invalid) {
                    this.ct()
                }
            },
            ct() {
                this.sending = true
                this.axios.post('/user/login', {
                    username: this.form.username,
                    password: this.form.password,
                    remember: this.form.remember
                }).then((response) => {
                    if (response.data.code === 200) {
                        this.$emit("showMsg", this.$t('logged-in'))
                        window.setTimeout(() => {
                            var uri = this.$route.query.redirect
                            if (uri)
                                window.location.href = uri
                            else
                                this.$emit("reload")
                        }, 1500)
                    } else {
                        if(response.data["data"])
                            this.$emit("showMsg", response.data["data"])
                        else
                            this.$emit("showMsg", this.$t('login-failed'))
                    }
                    this.sending = false
                }).catch((error) => {
                    this.sending = false
                    this.$emit("generalError",error)
                })

            }
        }, mounted: function () {
            this.$emit("changeTitle", this.$t("login-title"))
        }, watch: {
            gResponse: {
                handler: function (val, newVal) {
                    this.ct();
                }
            },
            isLoggedIn: {
                handler(val, newVal) {
                    if(newVal) {
                        let uri = this.$route.query.redirect
                        if (uri)
                            window.location.href = uri
                        else
                            this.router.go(-1)
                    }
                }
            }
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