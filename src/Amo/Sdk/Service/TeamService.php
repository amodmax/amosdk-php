<?php

namespace Amo\Sdk\Service;

use Amo\Sdk\AmoClient;
use Amo\Sdk\Models\FreeModel;
use Amo\Sdk\Models\Team;
use Amo\Sdk\Models\TeamProps;
use Amo\Sdk\Models\User;
use Amo\Sdk\Service\SubjectService;
use Amo\Sdk\Traits\ServiceInitializer;
use League\OAuth2\Client\Token\AccessToken;

/**
 * @method SubjectService subject(string $subjectId = null)
 */
class TeamService extends AbstractService
{
    use ServiceInitializer;

    protected ?string $teamId;

    public function factory(AmoClient $apiClient, string $teamId = null)
    {
        $this->teamId = $teamId;
        $this->apiClient = $apiClient;
    }

    public function create(Team $team): Team
    {
        $resp = $this->apiClient->post(
            $this->teamUrl(),
            [
                'body' => $team,
            ]
        );
        return Team::fromStream($resp->getBody());
    }

    public function invite(string $userId, TeamProps $teamProps = null): User
    {
        $resp = $this->apiClient->post(
            $this->teamUrl('/invite'),
            [
            'body' => new FreeModel(
                [
                    'user_id' => $userId,
                    'team_props' => $teamProps
                ]
            ),
            ]
        );
        return User::fromStream($resp->getBody());
    }

    public function kick(string $userId): void
    {
        $this->apiClient->delete($this->teamUrl(['users', $userId]));
    }

    /**
     * @param  string|string[] $location
     * @return string
     */
    public function teamUrl($location = ''): string
    {
        if (is_array($location)) {
            $location = implode('/', $location);
        }
        $url = '/teams';
        if ($this->teamId) {
            $url .= '/' . $this->teamId;
        }
        if ($location != '') {
            $url .= '/' . ltrim($location, '/');
        }
        return $url;
    }

    public function scope(): TeamService
    {
        $apiClient = $this->apiClient->withToken(
            $this->apiClient->getTeamAccessToken($this->teamId)
        );
        return $apiClient->team($this->teamId);

        //этот код выдавал ошибку: Error : Function name must be a string
//        return (new TeamService())(
//            $this->apiClient->withToken(
//                $this->apiClient->getTeamAccessToken($this->teamId)
//            ),
//            $this->teamId
//        );
    }

    public function getAccessToken(): ?AccessToken
    {
        return $this->apiClient->getAccessToken();
    }
}
