<?php

namespace App\Services\Searchers;

use App\Models\Blog;
use App\Models\WpSitemeta;
use App\Models\WpUser;
use App\Models\WpUsermeta;

class UsersSearcher extends BlogSearcher
{
    protected array $already = [];
    protected array $headers = [
        'User&nbsp;ID' => '5%',
        'Login Name' => '10%',
        'Email' => '15%',
        'Super Admin' => '10%',
        'Roles' => '70%'
    ];

    public function run(?string $searchText, array $options): BlogSearcher
    {
        $exact = $options['exact'] ?? false;

        $expression = $exact ? '=' : 'LIKE';
        $search = $exact ? $searchText : '%' . $searchText . '%';

        $users = WpUser::where('user_login', $expression, $search);

        $users->each(function ($user) {
            $this->found->push([
                'user_id' => $user->ID,
                'user_login' => $user->user_login,
                'user_email' => $user->user_email,
                'capabilities' => $this->getCapabilities($user->ID)
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
            $html .= '   <tr style="background-color: ' . $this->setRowColor($this->foundCount) . ';">';
            $html .= self::TABLE_CELL_CENTER;
            $html .= $item['user_id'];
            $html .= self::TABLE_CELL_END;
            $html .= self::TABLE_CELL_CENTER;
            $html .= $item['user_login'];
            $html .= self::TABLE_CELL_END;
            $html .= self::TABLE_CELL_TOP;
            $html .= $item['user_email'];
            $html .= self::TABLE_CELL_END;
            $html .= self::TABLE_CELL_CENTER;
            $html .= $this->isSuperAdmin($item['user_login']);
            $html .= self::TABLE_CELL_END;
            $html .= self::TABLE_CELL_TOP;
            $html .= $this->getCapabilities($item['user_id']);
            $html .= self::TABLE_CELL_END;
            $html .= self::TABLE_CELL_END;
            $html .= self::TABLE_ROW_END;

            $this->foundCount++;
        });
        $html .= self::TABLE_END;
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
