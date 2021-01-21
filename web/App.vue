<template>
  <div id="app">
    <header class="mb-3">
      <b-navbar toggleable="lg" type="dark" variant="dark">
        <b-navbar-brand href="https://github.com/mlocati/pecl-info" class="mr-1"
          >PECL Info</b-navbar-brand
        >
      </b-navbar>
    </header>
    <main>
      <b-container fluid="lg">
        <vue-bootstrap-typeahead
          class="mb-4"
          prepend="Package:"
          v-bind:min-matching-chars="1"
          v-bind:data="summary"
          v-bind:serializer="input => input.name"
          v-on:hit="
            selectedPackage = $event;
            updateLocationHash();
          "
        />
        <div v-if="selectedPackage !== null">
          <h2>{{ selectedPackage.name }}</h2>
          <b-alert show>
            <strong class="break">{{ selectedPackage.summary }}</strong>
            <div>License: <b-badge>{{ selectedPackage.license }}</b-badge></div>
            <div class="break">{{ selectedPackage.description }}</div>
          </b-alert>
          <b-alert
            v-if="
              !selectedPackage.phpv ||
                selectedPackage.phpv.length === 0 ||
                !selectedPackage.confopts ||
                selectedPackage.confopts.length === 0
            "
            show
            variant="warning"
          >
            No versions available
          </b-alert>
          <b-card
            v-if="selectedPackage.phpv && selectedPackage.phpv.length"
            header="PHP Versions"
            class="mb-2"
          >
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Package versions</th>
                  <th>Min PHP version</th>
                  <th>Max PHP version</th>
                </tr>
              </thead>
              <tbody>
                <view-php-versions
                  v-for="v in selectedPackage.phpv"
                  v-bind:key="selectedPackage.name + '@' + v.v.join(',')"
                  v-bind:data="v"
                />
              </tbody>
            </table>
          </b-card>
          <b-card
            v-if="selectedPackage.confopts && selectedPackage.confopts.length"
            header="Configure Options"
            class="mb-2"
          >
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Package versions</th>
                  <th>Options</th>
                </tr>
              </thead>
              <tbody>
                <view-configure-options
                  v-for="co in selectedPackage.confopts"
                  v-bind:key="selectedPackage.name + '@' + co.v.join(',')"
                  v-bind:data="co"
                />
              </tbody>
            </table>
          </b-card>
        </div>
      </b-container>
    </main>
  </div>
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator";
import VueBootstrapTypeahead from "vue-bootstrap-typeahead";
import { Summary, PackageSummary } from "./Summary";
import ViewPhpVersions from "./components/ViewPhpVersions.vue";
import ViewConfigureOptions from "./components/ViewConfigureOptions.vue";

@Component({
  components: {
    VueBootstrapTypeahead,
    ViewPhpVersions,
    ViewConfigureOptions
  }
})
export default class App extends Vue {
  private updatingHash = false;
  public selectedPackage: PackageSummary | null = null;
  public readonly summary: ReadonlyArray<PackageSummary> = Summary;
  public get packageNames(): Array<string> {
    const names: string[] = [];
    this.summary.forEach(summary => {
      names.push(summary.name);
    });
    return names;
  }
  private mounted(): void {
    this.inspectLocationHash();
    window.addEventListener(
      "hashchange",
      () => {
        if (this.updatingHash === false) {
          this.inspectLocationHash();
        }
      },
      false
    );
  }
  private inspectLocationHash(): void {
    const initialPackageName = (window.location.hash || "")
      .replace(/^#/, "")
      .toLowerCase();
    if (initialPackageName !== "") {
      this.summary.some((pkg: PackageSummary): boolean => {
        if (pkg.name.toLowerCase() !== initialPackageName) {
          return false;
        }
        this.selectedPackage = pkg;
        return true;
      });
    }
  }
  private updateLocationHash(): void {
    this.updatingHash = true;
    window.location.hash = this.selectedPackage
      ? `#${this.selectedPackage.name}`
      : "";
    this.updatingHash = false;
  }
}
</script>

<style scoped>
.break {
  white-space: pre-line;
}
</style>