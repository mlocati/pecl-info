<template>
  <tr>
    <td>{{ versionDescription }}</td>
    <td>
      <i v-if="!data.pkgs || data.pkgs.length === 0">
        No packages required
      </i>
      <ol v-else class="mb-0">
        <li v-for="pkg in data.pkgs" v-bind:key="pkg.n">
          <b>
            <a v-if="pkg.u" target="_blank" v-bind:href="pkg.u">
              {{ pkg.n }}
            </a>
            <template v-else>
              {{ pkg.n }}
            </template>
          </b>
          <b-badge v-if="pkg.m" class="ml-2">Min: {{ pkg.m }}</b-badge>
          <b-badge v-if="pkg.M" class="ml-2">Max: {{ pkg.M }}</b-badge>
        </li>
      </ol>
    </td>
  </tr>
</template>

<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator";
import { CompatibleRequiredPackages } from "../Summary";
@Component
export default class ViewRequiredPackages extends Vue {
  @Prop() private data!: CompatibleRequiredPackages;
  @Prop() private compactVersions!: boolean;
  private get versionDescription(): string {
    if (!this.compactVersions) {
      return this.data.v.join(", ");
    }
    if (this.data.v.length === 1) {
      return this.data.v[0];
    }
    return this.data.v[0] + " ➔ " + this.data.v[this.data.v.length - 1];
  }
}
</script>
