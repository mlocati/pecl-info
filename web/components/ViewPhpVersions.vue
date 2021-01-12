<template>
  <tr>
    <td>{{ versionDescription }}</td>
    <td>{{ data.min === undefined ? "" : data.min }}</td>
    <td>{{ data.max === undefined ? "" : data.max }}</td>
  </tr>
</template>

<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator";
import { CompatiblePHPVersions } from "../Summary";
@Component
export default class ViewCompatiblePhpVersions extends Vue {
  @Prop() private data!: CompatiblePHPVersions;
  private get versionDescription(): string {
    if (this.data.v.length === 1) {
      return this.data.v[0];
    }
    return this.data.v[0] + " ➔ " + this.data.v[this.data.v.length - 1];
  }
}
</script>
