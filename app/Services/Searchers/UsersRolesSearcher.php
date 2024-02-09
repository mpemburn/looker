<?php

namespace App\Services\Searchers;

use App\Models\Blog;
use App\Models\WpOption;
use App\Models\WpSitemeta;
use App\Models\WpUser;
use App\Models\WpUsermeta;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class UsersRolesSearcher extends BlogSearcher
{
    protected array $already = [];
    protected array $headers = [
        'Blog&nbsp;ID' => '25%',
//        'URL' => '25%',
//        'User ID' => '5%',
//        'User&nbsp;Email' => '25%',
//        'Updated' => '25%',
    ];

    public function run(?string $searchText, array $options): BlogSearcher
    {
        Log::debug('%"' . $searchText . '"%');
        $userRoles = WpUsermeta::where('meta_key', 'LIKE', 'wp_%_capabilities')
            ->where('meta_value', 'LIKE', '%"' . $searchText . '"%')
            ->get();

        $ids = collect();
        $userRoles->each(function ($roles) use (&$ids) {
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

            if ($ids->contains($user->ID)) {
                return;
            }

            $this->found->push([
                'blog_id' => $blog_id,
                'url' => 'https://' . $blog->domain . $blog->path,
                'path' => $blog->path,
                'user_id' => $user->ID,
                'user_email' => $user->user_email,
                'updated' => $blog->last_updated,
            ]);
            $ids->push($user->ID);
        });

        return $this;
    }

    public function process(string $blogId, string $blogUrl): bool
    {
        return true;
    }

    public function renderX(bool $showNotFound = false): string
    {
        $html = '';

        $found = $showNotFound ? $this->notFound : $this->found;
        $this->foundCount = 0;
        $html .= '<div style="font-family: sans-serif">';
        $html .= self::TABLE_TAG;
        $html .= $this->buildHeader();
        $found->each(function ($item) use (&$html) {
            if (in_array($item['blog_id'], $this->unique)) {
                //return;
            }
            $html .= '   <tr>';
            $html .= '      <td class="align-top text-left">';
            $html .= 'users["' . $item['path'] . '"]="' . $item['user_id'] . '"';
            $html .= self::TABLE_CELL_END;
            $html .= self::TABLE_ROW_END;

            $this->foundCount++;
            $this->unique[] = $item['blog_id'];
        })->sortBy('blog_id');
        $html .= self::TABLE_END;
        $html .= '<div>';

        return $html;
    }

    public function render(bool $showNotFound = false): string
    {
        $html = '';
        $ids = collect();

        $found = $showNotFound ? $this->notFound : $this->found;
        $this->foundCount = 0;
        $html .= '<div style="font-family: sans-serif">';
        $html .= self::TABLE_TAG;
        $html .= $this->buildHeader();
        $found->each(function ($item) use (&$html, &$ids) {
            if (in_array($item['blog_id'], $this->unique)) {
                return;
            }
            if (! $ids->contains($item['user_id'])) {
                $ids->push($item['user_id']);
            }
            $html .= '   <tr style="background-color: ' . $this->setRowColor($this->foundCount) . ';">';
            $html .= self::TABLE_CELL_CENTER;
            $html .= $item['blog_id'];
            $html .= self::TABLE_CELL_END;
            $html .= '      <td class="align-top text-left">';
            $html .= $this->makeLink($item['url']);
            $html .= self::TABLE_CELL_END;
            $html .= self::TABLE_CELL_CENTER;
            $html .= $item['user_id'];
            $html .= self::TABLE_CELL_END;
            $html .= '      <td class="align-top text-left">';
            $html .= $item['user_email'];
            $html .= self::TABLE_CELL_END;
            $html .= self::TABLE_CELL_RIGHT;
            $html .= Carbon::parse($item['updated'])->format('F j, Y');
            $html .= self::TABLE_CELL_END;
            $html .= self::TABLE_ROW_END;

            $this->foundCount++;
            $this->unique[] = $item['blog_id'];
        })->sortBy('blog_id');
        $html .= self::TABLE_END;
        $html .= '<div>';

        $idArray = $ids->implode(',');
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
