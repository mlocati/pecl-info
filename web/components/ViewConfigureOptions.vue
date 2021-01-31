<template>
  <tr>
    <td>{{ versionDescription }}</td>
    <td>
      <i v-if="!data.opts || data.opts.length === 0">
        No configure options
      </i>
      <ol v-else>
        <li v-for="option in data.opts" v-bind:key="option.n">
          <b>{{ option.p }}</b>
          <br />
          Name: <code>{{ option.n }}</code>
          <br />
          Default:
          <code v-if="option.d !== undefined">{{ option.d }}</code>
          <i v-else>none</i>
        </li>
      </ol>
    </td>
    <td>{{ data.max === undefined ? "" : data.max }}</td>
  </tr>
</template>

<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator";
import { CompatibleConfigureOptions } from "../Summary";
@Component
export default class ViewConfigureOptions extends Vue {
  @Prop() private data!: CompatibleConfigureOptions;
  private get versionDescription(): string {
    if (this.data.v.length === 1) {
      return this.data.v[0];
    }
    return this.data.v[0] + " ➔ " + this.data.v[this.data.v.length - 1];
  }
}
</script>
