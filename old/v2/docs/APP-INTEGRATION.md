# App integration contract

Apps use immutable `appId` values and are installed as UUID-based instances. Runtime identity is server-derived and contains the current user, tenant, groups and instance. Apps must never trust tenant IDs from query parameters. App-internal authorization remains inside the app.

A repository template or internal example must set all three catalog flags to `false`. The catalog loader only returns manifests whose `catalog.discoverable` value is true. A production catalog must additionally reject `installable=false` and `downloadable=false` before package retrieval.
