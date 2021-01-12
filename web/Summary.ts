import data from "./summary.min.json";

interface CompatiblePHPVersions {
  readonly v: ReadonlyArray<string>;
  readonly min?: string;
  readonly max?: string;
}

interface ConfigureOption {
  readonly n: string;
  readonly p: string;
  readonly d?: string;
}

interface CompatibleConfigureOptions {
  readonly v: ReadonlyArray<string>;
  readonly opts?: ReadonlyArray<ConfigureOption>;
}

export interface PackageSummary {
  readonly name: string;
  readonly phpv?: ReadonlyArray<CompatiblePHPVersions>;
  readonly confopts?: ReadonlyArray<CompatibleConfigureOptions>;
}

export const Summary = data as ReadonlyArray<PackageSummary>;
