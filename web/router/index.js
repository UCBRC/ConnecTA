import Vue from 'vue'
import Router from 'vue-router'
import Jump from '../components/Jump'
import App from '../components/Components/App'
import Dashboard from '../components/Dashboard'

import Directory from '../components/Alumni/Directory'
import Realname from '../components/Alumni/Realname'
import Form from '../components/Alumni/Form'

import Gallery from '../components/Media/Gallery'
import Album from '../components/Media/Album'
import Vote from '../components/School/Event'

import Login from '../components/User/Login'
import Register from '../components/User/Register'
import Reset from '../components/User/Reset'
import Security from '../components/User/Security'
import Message from '../components/User/Message'
import Info from '../components/User/Info'
import Public from '../components/User/Public'

import Preference from '../components/Admin/Preference'
import Upload from '../components/Admin/Upload'
import Notification from '../components/Admin/Notification'
import VoteAdmin from '../components/Admin/Vote'
import Old from '../components/Admin/Old'
import User from '../components/Admin/User'
import Overview from '../components/Admin/Overview'

Vue.use(Router)

export default new Router({
    routes: [
        {
            path: '/',
            component: App,
            children: [
                {
                    path: '/',
                    component: Dashboard
                }, {
                    path: '/alumni/auth',
                    component: Realname
                }, {
                    path: '/alumni/auth/:id',
                    component: Form
                }, {
                    path: '/alumni/auth/admin/:id',
                    component: Form
                }, {
                    path: '/alumni/directory',
                    component: Directory
                }, {
                    path: '/media/gallery',
                    component: Gallery
                }, {
                    path: '/media/gallery/:id',
                    component: Album
                }, {
                    path: '/event',
                    component: Vote
                }, {
                    path: '/user/security',
                    component: Security
                }, {
                    path: '/user/message',
                    component: Message
                }, {
                    path: '/user/message/:id',
                    component: Message
                }, {
                    path: '/user/login',
                    component: Login
                }, {
                    path: '/user/register',
                    component: Register
                }, {
                    path: '/user/reset',
                    component: Reset
                }, {
                    path: '/user/info',
                    component: Info
                }, {
                    path: '/user/page/:id',
                    component: Public
                }, {
                    path: '/admin/preference',
                    component: Preference
                }, {
                    path: '/admin/upload',
                    component: Upload
                }, {
                    path: '/admin/notification',
                    component: Notification
                }, {
                    path: '/admin/event',
                    component: VoteAdmin
                }, {
                    path: '/admin/old',
                    component: Old
                }, {
                    path: '/admin/user',
                    component: User
                }, {
                    path: '/admin/overview',
                    component: Overview
                }, {
                    path: '*',
                    component: Jump
                }
            ]
        }
    ]
})
