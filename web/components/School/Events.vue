<i18n src="../../translation/frontend/School.json"></i18n>
<template>
  <div id="events">
    <div class="md-layout md-gutter md-alignment-center">
      <div class="md-layout-item md-xlarge-size-20 md-large-size-33 md-medium-size-50 md-small-size-100 md-xsmall-size-100 gallery-card"
           v-for="item in items" :key="item.id">
        <md-card @click.native="onclick(item.id)">
          <md-card-media-cover md-solid>
            <md-card-media md-ratio="4:3">
              <img :src="item.thumbnail" alt="Skyscraper">
            </md-card-media>
            <md-card-area>
              <md-card-header>
                <span class="md-title">{{item.title}}</span>
              </md-card-header>
            </md-card-area>
          </md-card-media-cover>
        </md-card>
      </div>
    </div>
  </div>

</template>

<script>

export default {
  name: "event",
  props: ["name"],
  data: () => ({
    items: [],
  }),
  mounted: function () {
    this.$emit("changeTitle", this.$t('title-event'))
    this.loadMore()
  },
  methods: {
    onclick(id) {
      this.$router.push("/event/" + id)
    },
    loadMore() {
      this.axios.get("/event/list").then((response) => {
        this.items = response.data["data"]
      }).catch((error) => {
        this.$emit("generalError",error)
      })
    }
  },
}
</script>

<style scoped>
.gallery-card {
  margin-top: 10px;
  margin-bottom: 10px;
}

img {
  width: 100% !important;
  height: 100% !important;
  object-fit: cover !important;
  overflow: hidden;
  margin: 0px;
  padding: 0px;
}
</style>