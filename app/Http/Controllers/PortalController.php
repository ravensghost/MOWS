/**
     * Display a published Book by its URL slug.
     */
    public function showBook(string $bookUrl = '/'): ViewContract
    {
        $payload = $this->resolvePublishedBook(Book::normaliseUrl($bookUrl));

        return view('book', $payload);
    }

    /**
     * Display a Persona biography within the '/bio' Book layout.
     */
    public function showBio(Persona $persona): ViewContract
    {
        $payload = $this->resolvePublishedBook('/bio', 'public_label_Bio');

        $persona->load([
            'user',
            'lineageCatalogue',
            'insignia' => function ($query) {
                $query->where('is_visible', true)->with([
                    'insigniaCatalogue' => function ($q) {
                        $q->where('is_visible', true);
                    },
                ]);
            },
            'assignment' => function ($query) {
                $query->where('is_visible', true)->with([
                    'division' => function ($q) {
                        $q->where('is_visible', true);
                    },
                ]);
            },
            'candidatePetition.entries.metadata',
            'chronologicalRegisters.citation',
            'chronologicalRegisters.issuer',
            'attributes',
        ]);

        View::share('persona', $persona);
        $payload['persona'] = $persona;

        return view('book', $payload);
    }

    /**
     * Resolve a published Book, filter its modules by user clearance, and build the view payload.
     */
    protected function resolvePublishedBook(string $bookUrl, ?string $fallbackBookKey = null): array
    {
        $book = Book::forUrl($bookUrl)
            ->publishedWithLayout()
            ->firstOrFail();

        $user = auth()->user();

        $filteredModules = ($book->modules ?? collect())->filter(function ($module) use ($user) {
            if (! ($module->pivot->is_visible ?? true)) {
                return false;
            }

            $required = $module->pivot->access_clearance;

            if (empty($required)) {
                return true;
            }

            return $user ? $user->can($required) : false;
        })->values();

        $book->setRelation('modules', $filteredModules);

        $lookupUrl = Book::normaliseUrl($book->book_url);
        $navKey = Cache::remember('nav_key_' . md5($lookupUrl), 3600, function () use ($lookupUrl) {
            return DB::table('table_of_contents')
                ->where('destination_url', $lookupUrl)
                ->value('translation_code_key');
        });

        $defaultKey = $fallbackBookKey ?? ('public_label_' . ($book->book_url === '/' ? 'Landing' : Str::studly(basename($book->book_url))));
        $bookKey = $navKey ?: $defaultKey;

        $moduleTokens = $filteredModules->pluck('module_token')->toArray();

        $bulletins = null;
        if (! empty(array_intersect(['bulletins', 'module_bulletins', 'news'], $moduleTokens))) {
            $bulletins = Bulletin::with('author')
                ->orderBy('published_at', 'desc')
                ->take(5)
                ->get();
        }

        $folios = null;
        if (! empty(array_intersect(['folios', 'module_folios', 'folio', 'posts'], $moduleTokens))) {
            $folios = Folio::with('activePersona')
                ->where('folio_status', 'published')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
        }

        return [
            'book'      => $book,
            'navKey'    => $navKey,
            'bookKey'   => $bookKey,
            'bulletins' => $bulletins,
            'folios'    => $folios,
        ];
    }