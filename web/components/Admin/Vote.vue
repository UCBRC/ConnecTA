<template>
  <div align="left">
    <span class="md-body-2">活动管理</span>
    <md-field>
      <label>请选择</label>
      <md-select v-model="form.id" @md-selected="load">
        <md-option value="new">新建</md-option>
        <md-option v-for="event in events" :key="event.id" :value="event.id">{{ event.title }} - {{ event.id }}</md-option>
      </md-select>
    </md-field>
    <form>
      <md-field>
        <label>标题</label>
        <md-input v-model="form.title"></md-input>
      </md-field>
      <md-field>
        <label>缩略图</label>
        <md-input v-model="form.thumbnail"></md-input>
      </md-field>
      <span class="md-caption">内容</span>
      <mavon-editor v-model="form.content"/>
      <br/>
      <span class="md-caption">加入指引（预约活动后显示）</span>
      <mavon-editor v-model="form.instruction"/>
      <br/>

      <md-switch v-model="form.published">发布</md-switch>
      <br/>
      <md-button class="md-raised md-primary" @click="submit">保存</md-button>
    </form>

  </div>
</template>

<script>
import vueJsonEditor from 'vue-json-editor'

export default {
  components: {
    vueJsonEditor
  },
  name: "VoteAdmin",
  data: () => ({
    events: [],
    form: {
      id: "new",
      title: "",
      content: "",
      thumbnail: "",
      instruction: "",
      published: false
    },
    total: 0,
    result: [],
    showDialog: false

  }),
  mounted() {
    this.list()
    this.$emit("changeTitle", "活动管理")
  },
  methods: {
    submit() {
      this.axios.post("/event/edit?id=" + this.form.id, this.form).then((response) => {
        this.form = response.data["data"]
        this.list()
      })
    },
    load() {
      this.axios.get("/event/edit?id=" + this.form.id, this.form).then((response) => {
        this.form = response.data["data"]
        this.result = []
      })
    },
    list() {
      this.axios.get("/event/list").then((response) => {
        let t_new = this.form.id === "new";
        this.events = response.data["data"]
        if (this.events.length > 0 && t_new)
          this.form.id = this.events[0].id
      })
    }
  }
}
</script>

<style scoped>

</style>