<template>
  <div id="app">
    <header class="mb-3">
      <b-navbar toggleable="lg" type="dark" variant="dark">
        <b-navbar-brand href="https://github.com/mlocati/pecl-info" class="mr-1"
          >PECL Info
        </b-navbar-brand>
      </b-navbar>
    </header>
    <main>
      <b-container fluid="lg">
        <b-alert
          v-if="loadError !== null"
          show
          variant="danger"
          class="load-error"
        >
          {{ loadError }}
        </b-alert>
        <b-alert v-else-if="summary === null" show variant="info">
          Loading...
        </b-alert>
        <div v-else>
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
          <div class="float-right mt-n3">
            <b-form-checkbox
              v-model="compactVersions"
              name="check-button"
              switch
            >
              Compact versions
            </b-form-checkbox>
          </div>
          <div v-if="selectedPackage !== null">
            <h2>{{ selectedPackage.name }}</h2>
            <b-alert show>
              <strong class="break">{{ selectedPackage.summary }}</strong>
              <div>
                License: <b-badge>{{ selectedPackage.license }}</b-badge>
              </div>
              <div class="break">{{ selectedPackage.description }}</div>
              <div class="clearfix">
                <div class="float-right">
                  <b-button
                    v-bind:href="
                      `https://pecl.php.net/package-changelog.php?package=${selectedPackage.name}`
                    "
                    size="sm"
                    variant="info"
                    >Changelog</b-button
                  >
                  &nbsp;
                  <b-button
                    v-bind:href="
                      `https://pecl.php.net/package/${selectedPackage.name}`
                    "
                    size="sm"
                    variant="primary"
                    >More details</b-button
                  >
                </div>
              </div>
            </b-alert>
            <b-card header="PHP Versions" class="mb-2 pi-section">
              <div
                v-if="!selectedPackage.phpv || !selectedPackage.phpv.length"
                class="text-muted"
              >
                No data available.
              </div>
              <table v-else class="table table-striped table-sm my-0 pi-section">
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
                    v-bind:compact-versions="compactVersions"
                  />
                </tbody>
              </table>
            </b-card>
            <b-card header="Configure Options" class="mb-2 pi-section">
              <div
                v-if="
                  !selectedPackage.confopts || !selectedPackage.confopts.length
                "
                class="text-muted"
              >
                No data available.
              </div>
              <table v-else class="table table-striped table-sm my-0 pi-section">
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
                    v-bind:compact-versions="compactVersions"
                  />
                </tbody>
              </table>
            </b-card>
            <b-card header="Required Packages" class="mb-2 pi-section">
              <div
                v-if="
                  !selectedPackage.reqpkgs || !selectedPackage.reqpkgs.length
                "
                class="text-muted"
              >
                No data available.
              </div>
              <table v-else class="table table-striped table-sm my-0 pi-section">
                <thead>
                  <tr>
                    <th>Package versions</th>
                    <th>Required package</th>
                  </tr>
                </thead>
                <tbody>
                  <view-required-packages
                    v-for="rp in selectedPackage.reqpkgs"
                    v-bind:key="selectedPackage.name + '@' + rp.name"
                    v-bind:data="rp"
                    v-bind:compact-versions="compactVersions"
                  />
                </tbody>
              </table>
            </b-card>
          </div>
        </div>
      </b-container>
    </main>
  </div>
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator";
import VueBootstrapTypeahead from "vue-bootstrap-typeahead";
import { getSummary, PackageSummary } from "./Summary";
import ViewPhpVersions from "./components/ViewPhpVersions.vue";
import ViewConfigureOptions from "./components/ViewConfigureOptions.vue";
import ViewRequiredPackages from "./components/ViewRequiredPackages.vue";

@Component({
  components: {
    VueBootstrapTypeahead,
    ViewPhpVersions,
    ViewConfigureOptions,
    ViewRequiredPackages
  }
})
export default class App extends Vue {
  private updatingHash = false;
  public selectedPackage: PackageSummary | null = null;
  public compactVersions = true;
  public summary: ReadonlyArray<PackageSummary> | null = null;
  public loadError: Error | null = null;
  public get packageNames(): Array<string> {
    const names: string[] = [];
    if (this.summary !== null) {
      this.summary.forEach(summary => {
        names.push(summary.name);
      });
    }
    return names;
  }
  private mounted(): void {
    getSummary()
      .then((summary: ReadonlyArray<PackageSummary>) => {
        this.summary = summary;
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
      })
      .catch((error: Error) => {
        this.loadError = error;
      });
  }
  private inspectLocationHash(): void {
    const initialPackageName = (window.location.hash || "")
      .replace(/^#/, "")
      .toLowerCase();
    if (initialPackageName !== "" && this.summary !== null) {
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
.load-error {
  white-space: pre-wrap;
}
.card.pi-section>.card-header {
  font-weight: bold;
}
table.pi-section>thead>tr>* {
  border-top: 0;
}
</style>
