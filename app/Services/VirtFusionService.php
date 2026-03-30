<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;

class VirtFusionService
{
    private string $baseUrl;
    private string $apiToken;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('virtfusion.url'), '/');
        $this->apiToken = config('virtfusion.api_token');
    }

    private function request(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withToken($this->apiToken)
            ->baseUrl($this->baseUrl . '/api/v1')
            ->acceptJson()
            ->timeout(30);
    }

    private function handleResponse(Response $response, string $context = ''): array
    {
        if ($response->successful()) {
            return $response->json() ?? [];
        }

        Log::error("VirtFusion API error [{$context}]", [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        throw new \Exception("VirtFusion API fout: {$response->status()} - {$context}");
    }

    // ── General ──────────────────────────────────────────────────────
    // GET /connect
    public function testConnection(): bool
    {
        try {
            $response = $this->request()->get('/connect');
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    // ── Hypervisors ──────────────────────────────────────────────────
    // GET /compute/hypervisors
    public function getHypervisors(): array
    {
        $response = $this->request()->get('/compute/hypervisors');
        return $this->handleResponse($response, 'getHypervisors');
    }

    // GET /compute/hypervisors/{hypervisorId}
    public function getHypervisor(int $hypervisorId): array
    {
        $response = $this->request()->get("/compute/hypervisors/{$hypervisorId}");
        return $this->handleResponse($response, 'getHypervisor');
    }

    // ── Hypervisor Groups ────────────────────────────────────────────
    // GET /compute/hypervisors/groups
    public function getHypervisorGroups(): array
    {
        $response = $this->request()->get('/compute/hypervisors/groups');
        return $this->handleResponse($response, 'getHypervisorGroups');
    }

    // GET /compute/hypervisors/groups/{hypervisorGroupId}
    public function getHypervisorGroup(int $groupId): array
    {
        $response = $this->request()->get("/compute/hypervisors/groups/{$groupId}");
        return $this->handleResponse($response, 'getHypervisorGroup');
    }

    // GET /compute/hypervisors/groups/{hypervisorGroupId}/resources
    public function getHypervisorGroupResources(int $groupId): array
    {
        $response = $this->request()->get("/compute/hypervisors/groups/{$groupId}/resources");
        return $this->handleResponse($response, 'getHypervisorGroupResources');
    }

    // ── Users ────────────────────────────────────────────────────────
    // POST /users
    public function createUser(string $name, string $email, int $extRelationId, array $options = []): array
    {
        $payload = array_merge([
            'name' => $name,
            'email' => $email,
            'extRelationId' => $extRelationId,
            'sendMail' => false,
        ], $options);

        $response = $this->request()->post('/users', $payload);
        return $this->handleResponse($response, 'createUser');
    }

    // ── Users / External Rel ID & Rel Str ────────────────────────────
    // GET /users/{extRelationId}/byExtRelation
    public function getUser(int $extRelationId): array
    {
        $response = $this->request()->get("/users/{$extRelationId}/byExtRelation");
        return $this->handleResponse($response, 'getUser');
    }

    // PUT /users/{extRelationId}/byExtRelation
    public function modifyUser(int $extRelationId, array $data): array
    {
        $response = $this->request()->put("/users/{$extRelationId}/byExtRelation", $data);
        return $this->handleResponse($response, 'modifyUser');
    }

    // DELETE /users/{extRelationId}/byExtRelation
    public function deleteUser(int $extRelationId): array
    {
        $response = $this->request()->delete("/users/{$extRelationId}/byExtRelation");
        return $this->handleResponse($response, 'deleteUser');
    }

    // POST /users/{extRelationId}/authenticationTokens
    public function generateLoginTokens(int $extRelationId): array
    {
        $response = $this->request()->post("/users/{$extRelationId}/authenticationTokens");
        return $this->handleResponse($response, 'generateLoginTokens');
    }

    // POST /users/{extRelationId}/serverAuthenticationTokens/{serverId}
    public function generateLoginTokensByServer(int $extRelationId, int $serverId): array
    {
        $response = $this->request()->post("/users/{$extRelationId}/serverAuthenticationTokens/{$serverId}");
        return $this->handleResponse($response, 'generateLoginTokensByServer');
    }

    // POST /users/{extRelationId}/byExtRelation/resetPassword
    public function changeUserPassword(int $extRelationId): array
    {
        $response = $this->request()->post("/users/{$extRelationId}/byExtRelation/resetPassword");
        return $this->handleResponse($response, 'changeUserPassword');
    }

    // ── Packages ─────────────────────────────────────────────────────
    // GET /packages
    public function getPackages(): array
    {
        $response = $this->request()->get('/packages');
        return $this->handleResponse($response, 'getPackages');
    }

    // GET /packages/{packageId}
    public function getPackage(int $packageId): array
    {
        $response = $this->request()->get("/packages/{$packageId}");
        return $this->handleResponse($response, 'getPackage');
    }

    // ── Media ────────────────────────────────────────────────────────
    // GET /media/iso/{isoId}
    public function getIso(int $isoId): array
    {
        $response = $this->request()->get("/media/iso/{$isoId}");
        return $this->handleResponse($response, 'getIso');
    }

    // GET /media/templates/fromServerPackageSpec/{serverPackageId}
    public function getPackageOsTemplates(int $serverPackageId): array
    {
        $response = $this->request()->get("/media/templates/fromServerPackageSpec/{$serverPackageId}");
        return $this->handleResponse($response, 'getPackageOsTemplates');
    }

    // ── Servers ──────────────────────────────────────────────────────
    // POST /servers
    public function createServer(int $packageId, int $userId, int $hypervisorId, array $options = []): array
    {
        $payload = array_merge([
            'packageId' => $packageId,
            'userId' => $userId,
            'hypervisorId' => $hypervisorId,
            'ipv4' => config('virtfusion.default_ipv4', 1),
        ], $options);

        $response = $this->request()->post('/servers', $payload);
        return $this->handleResponse($response, 'createServer');
    }

    // GET /servers
    public function getServers(): array
    {
        $response = $this->request()->get('/servers');
        return $this->handleResponse($response, 'getServers');
    }

    // GET /servers?page={page} (paginated)
    public function getServersPaginated(int $page = 1): array
    {
        $response = $this->request()->get('/servers', ['page' => $page]);
        return $this->handleResponse($response, 'getServersPaginated');
    }

    // GET /servers/{serverId}
    public function getServer(int $serverId): array
    {
        $response = $this->request()->get("/servers/{$serverId}");
        return $this->handleResponse($response, 'getServer');
    }

    // DELETE /servers/{serverId}
    public function deleteServer(int $serverId): array
    {
        $response = $this->request()->delete("/servers/{$serverId}");
        return $this->handleResponse($response, 'deleteServer');
    }

    // POST /servers/{serverId}/build
    public function buildServer(int $serverId, int $osTemplateId, array $options = []): array
    {
        $payload = array_merge([
            'operatingSystemId' => $osTemplateId,
            'vnc' => config('virtfusion.enable_vnc', true),
            'ipv6' => config('virtfusion.enable_ipv6', true),
            'swap' => config('virtfusion.default_swap', 512),
            'email' => true,
        ], $options);

        $response = $this->request()->post("/servers/{$serverId}/build", $payload);
        return $this->handleResponse($response, 'buildServer');
    }

    // PUT /servers/{serverId}/package/{packageId}
    public function changeServerPackage(int $serverId, int $packageId, array $options = []): array
    {
        $payload = array_merge([
            'cpu' => true,
            'memory' => true,
            'primaryDiskSize' => true,
            'primaryNetworkTraffic' => true,
            'primaryNetworkInboundSpeed' => true,
            'primaryNetworkOutboundSpeed' => true,
            'primaryDiskWriteIOPS' => true,
            'primaryDiskWriteThroughput' => true,
            'primaryDiskReadIOPS' => false,
            'primaryDiskReadThroughput' => false,
            'backupPlan' => true,
        ], $options);

        $response = $this->request()->put("/servers/{$serverId}/package/{$packageId}", $payload);
        return $this->handleResponse($response, 'changeServerPackage');
    }

    // PUT /servers/{serverId}/modify/name
    public function modifyServerName(int $serverId, string $name): array
    {
        $response = $this->request()->put("/servers/{$serverId}/modify/name", ['name' => $name]);
        return $this->handleResponse($response, 'modifyServerName');
    }

    // POST /servers/{serverId}/resetPassword
    public function resetServerPassword(int $serverId): array
    {
        $response = $this->request()->post("/servers/{$serverId}/resetPassword");
        return $this->handleResponse($response, 'resetServerPassword');
    }

    // GET /servers/user/{userId}
    public function getUserServers(int $userId): array
    {
        $response = $this->request()->get("/servers/user/{$userId}");
        return $this->handleResponse($response, 'getUserServers');
    }

    // GET /servers/{serverId}/templates
    public function getServerOsTemplates(int $serverId): array
    {
        $response = $this->request()->get("/servers/{$serverId}/templates");
        return $this->handleResponse($response, 'getServerOsTemplates');
    }

    // PUT /servers/{serverId}/backups/plan/{planId}
    public function modifyBackupPlan(int $serverId, int $planId, array $data = []): array
    {
        $response = $this->request()->put("/servers/{$serverId}/backups/plan/{$planId}", $data);
        return $this->handleResponse($response, 'modifyBackupPlan');
    }

    // POST /servers/{serverId}/suspend
    public function suspendServer(int $serverId): array
    {
        $response = $this->request()->post("/servers/{$serverId}/suspend");
        return $this->handleResponse($response, 'suspendServer');
    }

    // POST /servers/{serverId}/unsuspend
    public function unsuspendServer(int $serverId): array
    {
        $response = $this->request()->post("/servers/{$serverId}/unsuspend");
        return $this->handleResponse($response, 'unsuspendServer');
    }

    // PUT /servers/{serverId}/modify/cpuThrottle
    public function throttleServerCpu(int $serverId, array $data): array
    {
        $response = $this->request()->put("/servers/{$serverId}/modify/cpuThrottle", $data);
        return $this->handleResponse($response, 'throttleServerCpu');
    }

    // PUT /servers/{serverId}/modify/memory
    public function modifyServerMemory(int $serverId, array $data): array
    {
        $response = $this->request()->put("/servers/{$serverId}/modify/memory", $data);
        return $this->handleResponse($response, 'modifyServerMemory');
    }

    // PUT /servers/{serverId}/modify/cpuCores
    public function modifyServerCpuCores(int $serverId, array $data): array
    {
        $response = $this->request()->put("/servers/{$serverId}/modify/cpuCores", $data);
        return $this->handleResponse($response, 'modifyServerCpuCores');
    }

    // PUT /servers/{serverId}/owner/{newOwnerId}
    public function changeServerOwner(int $serverId, int $newOwnerId): array
    {
        $response = $this->request()->put("/servers/{$serverId}/owner/{$newOwnerId}");
        return $this->handleResponse($response, 'changeServerOwner');
    }

    // POST /servers/{serverId}/customXML
    public function setCustomXml(int $serverId, array $data): array
    {
        $response = $this->request()->post("/servers/{$serverId}/customXML", $data);
        return $this->handleResponse($response, 'setCustomXml');
    }

    // ── Server Power ─────────────────────────────────────────────────
    // POST /servers/{serverId}/power/boot
    public function bootServer(int $serverId): array
    {
        $response = $this->request()->post("/servers/{$serverId}/power/boot");
        return $this->handleResponse($response, 'bootServer');
    }

    // POST /servers/{serverId}/power/shutdown
    public function shutdownServer(int $serverId): array
    {
        $response = $this->request()->post("/servers/{$serverId}/power/shutdown");
        return $this->handleResponse($response, 'shutdownServer');
    }

    // POST /servers/{serverId}/power/restart
    public function restartServer(int $serverId): array
    {
        $response = $this->request()->post("/servers/{$serverId}/power/restart");
        return $this->handleResponse($response, 'restartServer');
    }

    // POST /servers/{serverId}/power/poweroff
    public function poweroffServer(int $serverId): array
    {
        $response = $this->request()->post("/servers/{$serverId}/power/poweroff");
        return $this->handleResponse($response, 'poweroffServer');
    }

    // ── VNC ──────────────────────────────────────────────────────────
    // POST /servers/{serverId}/vnc
    public function enableVnc(int $serverId): array
    {
        $response = $this->request()->post("/servers/{$serverId}/vnc", ['vnc' => true]);
        return $this->handleResponse($response, 'enableVnc');
    }

    public function disableVnc(int $serverId): array
    {
        $response = $this->request()->post("/servers/{$serverId}/vnc", ['vnc' => false]);
        return $this->handleResponse($response, 'disableVnc');
    }

    // GET /servers/{serverId}/vnc
    public function getVncDetails(int $serverId): array
    {
        $response = $this->request()->get("/servers/{$serverId}/vnc");
        return $this->handleResponse($response, 'getVncDetails');
    }

    // ── Server Network ───────────────────────────────────────────────
    // POST /servers/{serverId}/networkWhitelist
    public function addToNetworkWhitelist(int $serverId, array $data): array
    {
        $response = $this->request()->post("/servers/{$serverId}/networkWhitelist", $data);
        return $this->handleResponse($response, 'addToNetworkWhitelist');
    }

    // DELETE /servers/{serverId}/networkWhitelist
    public function removeFromNetworkWhitelist(int $serverId, array $data): array
    {
        $response = $this->request()->delete("/servers/{$serverId}/networkWhitelist", $data);
        return $this->handleResponse($response, 'removeFromNetworkWhitelist');
    }

    // POST /servers/{serverId}/ipv4Qty
    public function addIpv4Quantity(int $serverId, int $quantity): array
    {
        $response = $this->request()->post("/servers/{$serverId}/ipv4Qty", ['quantity' => $quantity]);
        return $this->handleResponse($response, 'addIpv4Quantity');
    }

    // POST /servers/{serverId}/ipv4
    public function addIpv4Addresses(int $serverId, array $addresses): array
    {
        $response = $this->request()->post("/servers/{$serverId}/ipv4", ['addresses' => $addresses]);
        return $this->handleResponse($response, 'addIpv4Addresses');
    }

    // DELETE /servers/{serverId}/ipv4
    public function removeIpv4Addresses(int $serverId, array $addresses): array
    {
        $response = $this->request()->delete("/servers/{$serverId}/ipv4", ['addresses' => $addresses]);
        return $this->handleResponse($response, 'removeIpv4Addresses');
    }

    // ── Server Network / Firewall ────────────────────────────────────
    // POST /servers/{serverId}/firewall/{interface}/enable
    public function enableFirewall(int $serverId, string $interface = 'eth0'): array
    {
        $response = $this->request()->post("/servers/{$serverId}/firewall/{$interface}/enable");
        return $this->handleResponse($response, 'enableFirewall');
    }

    // POST /servers/{serverId}/firewall/{interface}/disable
    public function disableFirewall(int $serverId, string $interface = 'eth0'): array
    {
        $response = $this->request()->post("/servers/{$serverId}/firewall/{$interface}/disable");
        return $this->handleResponse($response, 'disableFirewall');
    }

    // GET /servers/{serverId}/firewall/{interface}
    public function getFirewall(int $serverId, string $interface = 'eth0'): array
    {
        $response = $this->request()->get("/servers/{$serverId}/firewall/{$interface}");
        return $this->handleResponse($response, 'getFirewall');
    }

    // POST /servers/{serverId}/firewall/{interface}/rules
    public function applyFirewallRulesets(int $serverId, string $interface, array $rulesets): array
    {
        $response = $this->request()->post("/servers/{$serverId}/firewall/{$interface}/rules", ['rulesets' => $rulesets]);
        return $this->handleResponse($response, 'applyFirewallRulesets');
    }

    // ── Server Network / Traffic ─────────────────────────────────────
    // GET /servers/{serverId}/traffic
    public function getServerTrafficStats(int $serverId): array
    {
        $response = $this->request()->get("/servers/{$serverId}/traffic");
        return $this->handleResponse($response, 'getServerTrafficStats');
    }

    // POST /servers/{serverId}/traffic/blocks
    public function addTrafficBlock(int $serverId, array $data): array
    {
        $response = $this->request()->post("/servers/{$serverId}/traffic/blocks", $data);
        return $this->handleResponse($response, 'addTrafficBlock');
    }

    // GET /servers/{serverId}/traffic/blocks
    public function getTrafficBlocks(int $serverId): array
    {
        $response = $this->request()->get("/servers/{$serverId}/traffic/blocks");
        return $this->handleResponse($response, 'getTrafficBlocks');
    }

    // DELETE /servers/{serverId}/traffic/blocks/{blockId}
    public function removeTrafficBlock(int $serverId, int $blockId): array
    {
        $response = $this->request()->delete("/servers/{$serverId}/traffic/blocks/{$blockId}");
        return $this->handleResponse($response, 'removeTrafficBlock');
    }

    // PUT /servers/{serverId}/modify/traffic
    public function modifyTrafficAllowance(int $serverId, array $data): array
    {
        $response = $this->request()->put("/servers/{$serverId}/modify/traffic", $data);
        return $this->handleResponse($response, 'modifyTrafficAllowance');
    }

    // ── IP Blocks ────────────────────────────────────────────────────
    // GET /connectivity/ipblocks
    public function getIpBlocks(): array
    {
        $response = $this->request()->get('/connectivity/ipblocks');
        return $this->handleResponse($response, 'getIpBlocks');
    }

    // GET /connectivity/ipblocks/{blockId}
    public function getIpBlock(int $blockId): array
    {
        $response = $this->request()->get("/connectivity/ipblocks/{$blockId}");
        return $this->handleResponse($response, 'getIpBlock');
    }

    // POST /connectivity/ipblocks/{blockId}/ipv4
    public function addIpv4RangeToBlock(int $blockId, array $data): array
    {
        $response = $this->request()->post("/connectivity/ipblocks/{$blockId}/ipv4", $data);
        return $this->handleResponse($response, 'addIpv4RangeToBlock');
    }

    // ── Backups ──────────────────────────────────────────────────────
    // GET /backups/server/{serverId}
    public function getServerBackups(int $serverId): array
    {
        $response = $this->request()->get("/backups/server/{$serverId}");
        return $this->handleResponse($response, 'getServerBackups');
    }

    // ── DNS ──────────────────────────────────────────────────────────
    // GET /dns/services/{serviceId}
    public function getDnsService(int $serviceId): array
    {
        $response = $this->request()->get("/dns/services/{$serviceId}");
        return $this->handleResponse($response, 'getDnsService');
    }

    // ── SSH Keys ─────────────────────────────────────────────────────
    // POST /ssh_keys
    public function addSshKey(array $data): array
    {
        $response = $this->request()->post('/ssh_keys', $data);
        return $this->handleResponse($response, 'addSshKey');
    }

    // DELETE /ssh_keys/{keyId}
    public function deleteSshKey(int $keyId): array
    {
        $response = $this->request()->delete("/ssh_keys/{$keyId}");
        return $this->handleResponse($response, 'deleteSshKey');
    }

    // GET /ssh_keys/{keyId}
    public function getSshKey(int $keyId): array
    {
        $response = $this->request()->get("/ssh_keys/{$keyId}");
        return $this->handleResponse($response, 'getSshKey');
    }

    // GET /ssh_keys/user/{userId}
    public function getUserSshKeys(int $userId): array
    {
        $response = $this->request()->get("/ssh_keys/user/{$userId}");
        return $this->handleResponse($response, 'getUserSshKeys');
    }

    // ── Queue & Tasks ────────────────────────────────────────────────
    // GET /queue/{queueId}
    public function getQueueItem(int $queueId): array
    {
        $response = $this->request()->get("/queue/{$queueId}");
        return $this->handleResponse($response, 'getQueueItem');
    }

    // ── Self Service ─────────────────────────────────────────────────
    // POST /selfService/credit/byUserExtRelationId/{extRelationId}
    public function addCredit(int $extRelationId, array $data): array
    {
        $response = $this->request()->post("/selfService/credit/byUserExtRelationId/{$extRelationId}", $data);
        return $this->handleResponse($response, 'addCredit');
    }

    // DELETE /selfService/credit/{creditId}
    public function cancelCredit(int $creditId): array
    {
        $response = $this->request()->delete("/selfService/credit/{$creditId}");
        return $this->handleResponse($response, 'cancelCredit');
    }

    // POST /selfService/hourlyGroupProfile/byUserExtRelationId/{extRelationId}
    public function addHourlyGroupProfile(int $extRelationId, array $data): array
    {
        $response = $this->request()->post("/selfService/hourlyGroupProfile/byUserExtRelationId/{$extRelationId}", $data);
        return $this->handleResponse($response, 'addHourlyGroupProfile');
    }

    // DELETE /selfService/hourlyGroupProfile/{profileId}/byUserExtRelationId/{extRelationId}
    public function removeHourlyGroupProfile(int $profileId, int $extRelationId): array
    {
        $response = $this->request()->delete("/selfService/hourlyGroupProfile/{$profileId}/byUserExtRelationId/{$extRelationId}");
        return $this->handleResponse($response, 'removeHourlyGroupProfile');
    }

    // POST /selfService/resourceGroupProfile/byUserExtRelationId/{extRelationId}
    public function addResourceGroupProfile(int $extRelationId, array $data): array
    {
        $response = $this->request()->post("/selfService/resourceGroupProfile/byUserExtRelationId/{$extRelationId}", $data);
        return $this->handleResponse($response, 'addResourceGroupProfile');
    }

    // DELETE /selfService/resourceGroupProfile/{profileId}/byUserExtRelationId/{extRelationId}
    public function removeResourceGroupProfile(int $profileId, int $extRelationId): array
    {
        $response = $this->request()->delete("/selfService/resourceGroupProfile/{$profileId}/byUserExtRelationId/{$extRelationId}");
        return $this->handleResponse($response, 'removeResourceGroupProfile');
    }

    // POST /selfService/resourcePack/byUserExtRelationId/{extRelationId}
    public function addResourcePack(int $extRelationId, array $data): array
    {
        $response = $this->request()->post("/selfService/resourcePack/byUserExtRelationId/{$extRelationId}", $data);
        return $this->handleResponse($response, 'addResourcePack');
    }

    // GET /selfService/resourcePack/{packId}
    public function getResourcePack(int $packId): array
    {
        $response = $this->request()->get("/selfService/resourcePack/{$packId}");
        return $this->handleResponse($response, 'getResourcePack');
    }

    // PUT /selfService/resourcePack/{packId}
    public function modifyResourcePack(int $packId, array $data): array
    {
        $response = $this->request()->put("/selfService/resourcePack/{$packId}", $data);
        return $this->handleResponse($response, 'modifyResourcePack');
    }

    // DELETE /selfService/resourcePack/{packId}
    public function deleteResourcePack(int $packId): array
    {
        $response = $this->request()->delete("/selfService/resourcePack/{$packId}");
        return $this->handleResponse($response, 'deleteResourcePack');
    }

    // DELETE /selfService/resourcePackServers/{packId}
    public function deleteResourcePackServers(int $packId): array
    {
        $response = $this->request()->delete("/selfService/resourcePackServers/{$packId}");
        return $this->handleResponse($response, 'deleteResourcePackServers');
    }

    // POST /selfService/resourcePackServers/{packId}/suspend
    public function suspendResourcePackServers(int $packId): array
    {
        $response = $this->request()->post("/selfService/resourcePackServers/{$packId}/suspend");
        return $this->handleResponse($response, 'suspendResourcePackServers');
    }

    // POST /selfService/resourcePackServers/{packId}/unsuspend
    public function unsuspendResourcePackServers(int $packId): array
    {
        $response = $this->request()->post("/selfService/resourcePackServers/{$packId}/unsuspend");
        return $this->handleResponse($response, 'unsuspendResourcePackServers');
    }

    // GET /selfService/hourlyStats/byUserExtRelationId/{extRelationId}
    public function getHourlyStats(int $extRelationId): array
    {
        $response = $this->request()->get("/selfService/hourlyStats/byUserExtRelationId/{$extRelationId}");
        return $this->handleResponse($response, 'getHourlyStats');
    }

    // PUT /selfService/access/byUserExtRelationId/{extRelationId}
    public function modifyUserAccess(int $extRelationId, array $data): array
    {
        $response = $this->request()->put("/selfService/access/byUserExtRelationId/{$extRelationId}", $data);
        return $this->handleResponse($response, 'modifyUserAccess');
    }

    // GET /selfService/report/byUserExtRelationId/{extRelationId}
    public function generateReport(int $extRelationId): array
    {
        $response = $this->request()->get("/selfService/report/byUserExtRelationId/{$extRelationId}");
        return $this->handleResponse($response, 'generateReport');
    }

    // PUT /selfService/hourlyResourcePack/byUserExtRelationId/{extRelationId}
    public function setHourlyResourcePack(int $extRelationId, array $data): array
    {
        $response = $this->request()->put("/selfService/hourlyResourcePack/byUserExtRelationId/{$extRelationId}", $data);
        return $this->handleResponse($response, 'setHourlyResourcePack');
    }

    // GET /selfService/usage/byUserExtRelationId/{extRelationId}
    public function getUserUsage(int $extRelationId): array
    {
        $response = $this->request()->get("/selfService/usage/byUserExtRelationId/{$extRelationId}");
        return $this->handleResponse($response, 'getUserUsage');
    }

    // GET /selfService/currencies
    public function getCurrencies(): array
    {
        $response = $this->request()->get('/selfService/currencies');
        return $this->handleResponse($response, 'getCurrencies');
    }
}
