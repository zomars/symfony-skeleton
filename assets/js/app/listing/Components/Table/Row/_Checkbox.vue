<template>
  <div v-show="!sorting" key="checkbox" class="listing--checkbox">
    <div class="custom-control custom-checkbox">
      <input
        :id="`row-${id}`"
        v-model="selected"
        type="checkbox"
        class="custom-control-input"
      />
      <label
        class="custom-control-label"
        :for="`row-${id}`"
        @click="selected != !selected"
      ></label>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Checkbox',
  props: ['id'],
  data: () => {
    return {
      selected: false,
    };
  },
  computed: {
    selectAll() {
      return this.$store.getters['selecting/selectAll'];
    },
    sorting() {
      return this.$store.getters['general/getSorting'];
    },
  },
  watch: {
    selectAll() {
      this.selected = this.selectAll;
    },
    selected() {
      this.selected
        ? this.$store.dispatch('selecting/select', this.id)
        : this.$store.dispatch('selecting/deSelect', this.id);
    },
    sorting() {
      if (this.sorting) this.selected = false;
    },
  },
};
</script>
