<?php

namespace App\Services\Searchers;

use App\Models\Blog;
use App\Models\WpOption;
use App\Models\WpSitemeta;
use App\Models\WpUser;
use App\Models\WpUsermeta;
use Illuminate\Support\Carbon;

class UsersRolesSearcher extends BlogSearcher
{
    protected array $already = [];
    protected array $headers = [
        'Blog&nbsp;ID' => '5%',
        'URL' => '25%',
        'User&nbsp;Email' => '25%',
        'Updated' => '25%',
    ];

    public function run(?string $searchText, bool $exact = false, bool $verbose = false): BlogSearcher
    {
        $userRoles = WpUsermeta::where('meta_key', 'LIKE', 'wp_%_capabilities')
            ->where('meta_value', 'LIKE', '%"' . $searchText . '%')
            ->get();

        $userRoles->each(function ($roles) {
            $user = WpUser::where('ID', $roles->user_id)->first();
            $blog_id = preg_replace('/[^0-9]+/', '', $roles->meta_key);
            $blog = Blog::where('blog_id', $blog_id)
                ->where('archived', 0)
                ->where('deleted', 0)
                ->where('public', 1)
                ->first();
            if (! $blog) {
                return;
            }
            $this->found->push([
                'blog_id' => $blog_id,
                'url' => 'https://' . $blog->domain . $blog->path,
                'user_email' => $user->user_email,
                'updated' => $blog->last_updated,
            ]);
        });

        return $this;
    }

    public function process(string $blogId, string $blogUrl): bool
    {
        return true;
    }

    public function render(bool $showNotFound = false): string
    {
        $html = '';

        $found = $showNotFound ? $this->notFound : $this->found;
        $this->foundCount = 0;
        $html .= '<div style="font-family: sans-serif">';
        $html .= self::TABLE_TAG;
        $html .= $this->buildHeader();
        $found->each(function ($item) use (&$html) {
            if (in_array($item['blog_id'], $this->unique)) {
                return;
            }
            $html .= '   <tr style="background-color: ' . $this->setRowColor($this->foundCount) . ';">';
            $html .= '      <td class="align-top text-center">';
            $html .= $item['blog_id'];
            $html .= '      </td>';
            $html .= '      <td class="align-top text-left">';
            $html .= $this->makeLink($item['url']);
            $html .= '      </td>';
            $html .= '      <td class="align-top text-left">';
            $html .= $item['user_email'];
            $html .= '      </td>';
            $html .= '      <td class="align-top text-right">';
            $html .= Carbon::parse($item['updated'])->format('F j, Y');
            $html .= '      </td>';
            $html .= '   </tr>';

            $this->foundCount++;
            $this->unique[] = $item['blog_id'];
        })->sortBy('blog_id');
        $html .= '<table>';
        $html .= '<div>';

        return $html;
    }

    protected function isSuperAdmin(string $userLogin): string
    {
        $superAdmins = WpSitemeta::where('meta_key', 'site_admins');
        if ($superAdmins && $superAdmins->first()) {
            $list = unserialize($superAdmins->first()->meta_value);
            return in_array($userLogin, $list) ? 'Yes' : 'No';
        }

        return 'No';
    }

    protected function getCapabilities(string $userId): string
    {
        $capabilities = collect();
        $unique = collect();
        WpUsermeta::where('user_id', $userId)
            ->where('meta_key', 'LIKE', '%_capabilities')
            ->orderBy('umeta_id')
            ->each(function ($meta) use (&$capabilities, &$unique) {
                $blogId = preg_replace('/(.*)(\d+)(.*)/', '$2', $meta->meta_key);
                if ($unique->contains($blogId)) {
                    return;
                }
                $unique->push($blogId);
                $text = '';
                $blog = Blog::where('blog_id', $blogId);
                if ($blog && $blog->first()) {
                    $text .= ' <strong>' . $blog->first()->path . '</strong> — ';
                }
                if ($text === '') {
                    return;
                }
                $roles = collect(unserialize($meta->meta_value))->map(function ($bool, $role) {
                    return $role;
                })->sort()->implode(', ');
                $text .= $roles;
                $capabilities->push($text);
            });

        return $capabilities->unique()->sort()->implode('<br>');
    }

    protected function error(): void
    {
    }
}
