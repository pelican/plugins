# Subdomains (by Boy132 & HarlequinSin)

Allows users to create and manage custom subdomains (A/AAAA or SRV) for their game servers using Cloudflare DNS.

## Setup

[Create a Cloudflare API token](https://developers.cloudflare.com/fundamentals/api/get-started/create-token/) and enter it via the plugin settings.  
The token needs to have read permissions for `Zone.Zone` and write for `Zone.Dns`. For better security you can also set the `Zone Resources` to exclude certain domains and add the panel ip to the `Client IP Address Filtering`.

By default every server has a subdomain limit of 0. You can change this limit by editing the server in the admin area.

Note: You can't create subdomains for servers with `0.0.0.0` or `::` as allocation!

## Configuring domains

Each domain is composed of a name and an optional prefix. The name must be a valid Cloudflare Zone, while the prefix can be used to specify a subdomain on which the server subdomains will be created.

For example: when creating a subdomain `server1` on a domain with name `example.com` and prefix `abc`, the created record will be `server1.abc.example.com`.

## SRV Records

In order to create SRV records instead of A/AAAA you need to do the following:

1. Set a `SRV target` for the node
2. Add a [SRV service type](https://github.com/pelican/plugins/blob/main/subdomains/src/Enums/SRVServiceType.php#L10-L15) to the features of the egg. The format is `srv-` and then the service name, e.g. `srv-minecraft` or `srv-rust`.
