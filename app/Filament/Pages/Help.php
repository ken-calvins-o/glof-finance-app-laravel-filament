<?php

namespace App\Filament\Pages;

use App\Support\HelpTopics;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * The help centre.
 *
 * A SACCO treasurer is usually a volunteer who took the job because someone had
 * to, and the members reading their own statement have never used an accounting
 * app in their lives. Neither of them is going to read a PDF manual that lives
 * in a WhatsApp group. So the manual lives inside the app, one click from
 * everywhere, and every task in it ends with a link that opens the actual screen
 * — reading about a job and doing it are the same journey.
 *
 * Three decisions worth recording:
 *
 *  - The words live in {@see HelpTopics}, not here. Someone should be able to
 *    fix a sentence without reading a page class, and a test can walk the whole
 *    thing to check that every link still points at a screen that exists.
 *  - Searching is done in the browser. Typing into a Livewire-backed box costs a
 *    round trip per keystroke, and on a phone over a shared tunnel that feels
 *    broken. The help text is a few kilobytes and it is all on the page already,
 *    so Alpine hides what does not match and the box responds instantly.
 *  - Members see a smaller manual than treasurers. There is no point explaining
 *    how to issue a loan to somebody whose menu has no Loans in it; it reads as
 *    a permission they are missing rather than a job that is not theirs.
 */
class Help extends Page
{
    protected static string $view = 'filament.pages.help';

    protected static ?string $navigationIcon = 'heroicon-o-lifebuoy';

    protected static ?string $activeNavigationIcon = 'heroicon-s-lifebuoy';

    protected static ?string $navigationLabel = 'Help';

    protected static ?string $title = 'Help';

    protected static ?string $slug = 'help';

    /**
     * Sorted to sit under Overview and above the six task groups. Help is not a
     * task, so putting it inside one of them would be filing it under a job it
     * does not belong to.
     */
    protected static ?int $navigationSort = -1;

    protected ?string $subheading = 'How to do the everyday jobs, in plain words. Every step links to the screen it talks about.';

    /**
     * The sections this user is allowed to read.
     *
     * @return Collection<int, array>
     */
    public function getSections(): Collection
    {
        return HelpTopics::sections(auth()->user());
    }

    /**
     * Turn one topic into everything the view needs to draw it.
     *
     * The link is stored as a closure so that resolving a URL — which needs a
     * booted panel and an authenticated user — happens when the page renders
     * rather than when the content class is loaded.
     */
    public function present(array $topic): array
    {
        $link = $topic['link'] ?? null;

        return [
            'id' => $topic['id'],
            'title' => $topic['title'],
            'summary' => $this->format($topic['summary'] ?? ''),
            'steps' => array_map(fn (string $step) => $this->format($step), $topic['steps'] ?? []),
            'notes' => array_map(fn (string $note) => $this->format($note), $topic['notes'] ?? []),
            'image' => $this->image($topic['image'] ?? null),
            'link' => $link ? ['label' => $link['label'], 'url' => ($link['url'])()] : null,
            'audience' => $topic['audience'] ?? HelpTopics::EVERYONE,
            // Everything a search should match, flattened into one string the
            // browser can test against without walking the DOM.
            'haystack' => Str::lower(implode(' ', array_filter([
                $topic['title'],
                $topic['summary'] ?? '',
                implode(' ', $topic['steps'] ?? []),
                implode(' ', $topic['notes'] ?? []),
            ]))),
        ];
    }

    /**
     * The URL of a screenshot, or null if it has not been captured yet.
     *
     * Checked rather than assumed: a topic that refers to a picture nobody has
     * taken should quietly render without one, not leave a broken image in the
     * middle of an instruction.
     */
    protected function image(?string $file): ?string
    {
        if (! $file) {
            return null;
        }

        $path = public_path('images/help/'.$file);

        return file_exists($path)
            ? asset('images/help/'.$file).'?v='.filemtime($path)
            : null;
    }

    /**
     * Render the one piece of markup the help text is allowed to use.
     *
     * Steps name things the reader has to find on screen — **Record money in**,
     * **Money owed** — and those need to stand out from the sentence around
     * them, otherwise the instruction reads as prose and the reader has to hunt.
     * Everything is escaped first, so the emphasis is the only thing that can
     * come through as markup.
     */
    protected function format(string $text): HtmlString
    {
        $escaped = e($text);

        return new HtmlString(preg_replace(
            '/\*\*(.+?)\*\*/s',
            '<strong class="font-semibold text-gray-950 dark:text-white">$1</strong>',
            $escaped,
        ));
    }
}
