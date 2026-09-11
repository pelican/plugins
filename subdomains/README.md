# Subdomains (by Boy132 & HarlequinSin)

Allows users to create and manage custom subdomains (A/AAAA or SRV) for their game servers using Cloudflare DNS.

## Setup

[Create a Cloudflare API token](https://developers.cloudflare.com/fundamentals/api/get-started/create-token/) and enter it via the plugin settings.  
The token needs to have read permissions for `Zone.Zone` and write for `Zone.Dns`. For better security you can also set the `Zone Resources` to exclude certain domains and add the panel ip to the `Client IP Address Filtering`.

By default every server has a subdomain limit of 0. You can change this limit by editing the server in the admin area.

### Domains

Each domain is composed of a name and an optional prefix. The name must be a valid Cloudflare Zone, while the prefix can be used to specify a subdomain on which the server subdomains will be created.

For example: when creating a subdomain `server1` on a domain with name `example.com` and prefix `abc`, the created record will be `server1.abc.example.com`.

#### Domain restrictions

Domains can be configured to only permit subdomain creation under specific conditions:

- For each domain you can select which DNS Record types can be created on it
- For each domain you can select the nodes on which it is enabled. Servers on unselected nodes will not have the option to use this domain.

Leaving these fields empty will keep all record types / nodes enabled.

## Configuration

Subdomains support several different DNS Record types. Each type has different requirements before it can be created.

If a DNS Record type is not available, check whether it is enabled on the domain and whether all of it's requirements have been met.

### Server primary allocations

A and AAAA Subdomains will use the IP of the server's primary allocation as their target. SRV records will use the primary allocation's port as part of their target.

IPs such as `0.0.0.0` and `::` are considered invalid for the purposes of creating subdomains. They should be changed to proper IP addresses on which your servers can be reached.

**IMPORTANT: In order to create subdomains for a server, that server's primary allocation MUST have a valid IP address.** This also applies for CNAME and SRV Subdomains.

### Subdomain targets

CNAME and SRV Subdomains must point to a specific Subdomain target. These can be configured for every node individually in the admin area.

Note: According to [RFC2782](https://www.rfc-editor.org/info/rfc2782/), SRV records must always point to either an A or AAAA record. While some applications may handle SRV records pointing to CNAME records correctly, this can lead to undefined behavior.

### SRV service types

SRV Subdomains require an SRV service type. This must be configured in the egg features section. The format is `srv-` and then the service name, e.g. `srv-minecraft` or `srv-rust`.

You can find the list of currently supported SRV service types [here](https://github.com/pelican/plugins/blob/main/subdomains/src/Enums/SRVServiceType.php#L10-L15).
