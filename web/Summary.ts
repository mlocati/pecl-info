import axios, { AxiosResponse } from "axios";

export interface CompatiblePHPVersions {
  readonly v: ReadonlyArray<string>;
  readonly min?: string;
  readonly max?: string;
}

interface ConfigureOption {
  readonly n: string;
  readonly p: string;
  readonly d?: string;
}

export interface CompatibleConfigureOptions {
  readonly v: ReadonlyArray<string>;
  readonly opts?: ReadonlyArray<ConfigureOption>;
}

interface RequiredPackage {
  readonly n: string;
  readonly m?: string;
  readonly M?: string;
  readonly x?: ReadonlyArray<string>;
  readonly u?: string;
}

export interface CompatibleRequiredPackages {
  readonly v: ReadonlyArray<string>;
  readonly pkgs?: ReadonlyArray<RequiredPackage>;
}

export interface PackageSummary {
  readonly name: string;
  readonly license: string;
  readonly summary: string;
  readonly description: string;
  readonly phpv?: ReadonlyArray<CompatiblePHPVersions>;
  readonly confopts?: ReadonlyArray<CompatibleConfigureOptions>;
  readonly reqpkgs?: ReadonlyArray<CompatibleRequiredPackages>;
}

let data: ReadonlyArray<PackageSummary> | undefined;

export function getSummary(): Promise<ReadonlyArray<PackageSummary>> {
  return new Promise<ReadonlyArray<PackageSummary>>((resolve, reject) => {
    if (data !== undefined) {
      resolve(data);
      return;
    }
    axios
      .get("data/summary.min.json")
      .then((response: AxiosResponse) => {
        data = response.data as ReadonlyArray<PackageSummary>;
        resolve(data);
      })
      .catch((error: Error) => {
        reject(error);
      });
  });
}
