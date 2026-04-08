<?php

declare(strict_types=1);

final class MarketplaceController
{
    private ProductListing $listings;
    private ProductBid $bids;
    private Ad $ads;

    public function __construct()
    {
        $this->listings = new ProductListing();
        $this->bids = new ProductBid();
        $this->ads = new Ad();
    }

    public function index(): string
    {
        Auth::requireRole(['client', 'tasker', 'admin']);

        $filters = [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'city' => normalize_whitespace((string) ($_GET['city'] ?? '')),
            'min_price' => trim((string) ($_GET['min_price'] ?? '')),
            'max_price' => trim((string) ($_GET['max_price'] ?? '')),
            'sort' => trim((string) ($_GET['sort'] ?? 'newest')),
        ];
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $pagination = pagination_params($page, 24);

        return View::render('marketplace/index', [
            'pageTitle' => 'Marketplace',
            'filters' => $filters,
            'listings' => $this->listings->browseOpen($filters, $pagination['limit'], $pagination['offset']),
            'pagination' => pagination_meta($page, $pagination['per_page'], $this->listings->countBrowseOpen($filters)),
            'ads' => $this->ads->activeByPlacement('marketplace', 2, true),
        ]);
    }

    public function create(): string
    {
        Auth::requireRole(['client', 'tasker', 'admin']);
        SubscriptionAccess::requirePaidAccess('Creating marketplace listings requires an active subscription after your free trial.');

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            Csrf::verifyRequest();
            $input = Validator::trim($_POST);
            Session::setOldInput($input);
            $fieldErrors = Validator::marketplaceListingFields($input);

            if ($fieldErrors !== []) {
                return View::render('marketplace/create', [
                    'pageTitle' => 'Sell an Item',
                    'errors' => Validator::flattenFieldErrors($fieldErrors),
                    'fieldErrors' => $fieldErrors,
                ]);
            }

            $listingId = $this->listings->create([
                'seller_id' => (int) Auth::id(),
                'title' => normalize_whitespace((string) $input['title']),
                'description' => normalize_whitespace((string) $input['description']),
                'city' => normalize_whitespace((string) $input['city']),
                'region' => normalize_whitespace((string) ($input['region'] ?? '')),
                'country' => normalize_whitespace((string) $input['country']),
                'starting_price' => number_format((float) round((float) $input['starting_price']), 2, '.', ''),
            ]);

            Session::clearOldInput();
            Session::flash('success', 'Marketplace listing created.');
            redirect('marketplace/show', ['id' => $listingId]);
        }

        return View::render('marketplace/create', [
            'pageTitle' => 'Sell an Item',
            'errors' => [],
            'fieldErrors' => [],
        ]);
    }

    public function myListings(): string
    {
        Auth::requireRole(['client', 'tasker', 'admin']);

        return View::render('marketplace/my-listings', [
            'pageTitle' => 'My Listings',
            'listings' => $this->listings->forSeller((int) Auth::id()),
        ]);
    }

    public function offers(): string
    {
        Auth::requireRole(['client', 'tasker', 'admin']);

        return View::render('marketplace/offers', [
            'pageTitle' => 'My Marketplace Offers',
            'offers' => $this->bids->forBuyer((int) Auth::id()),
        ]);
    }

    public function show(): string
    {
        Auth::requireRole(['client', 'tasker', 'admin']);

        $listingId = (int) ($_GET['id'] ?? 0);

        if ($listingId <= 0) {
            Session::flash('error', 'Listing not found.');
            redirect('marketplace/index');
        }

        $listing = $this->listings->findById($listingId);

        if ($listing === null) {
            Session::flash('error', 'Listing not found.');
            redirect('marketplace/index');
        }

        $selectedBid = $this->bids->findSelectedForListing($listingId);
        $isOwner = (int) $listing['seller_id'] === (int) Auth::id();
        $isSelectedBuyer = $selectedBid !== null && (int) $selectedBid['buyer_id'] === (int) Auth::id();
        $isAdmin = Auth::role() === 'admin';

        if (!$isOwner && !$isSelectedBuyer && !$isAdmin) {
            Session::flash('error', 'You do not have permission to open that listing workspace.');
            if ((string) $listing['status'] === 'open' && (int) $listing['is_active'] === 1) {
                redirect('marketplace/view', ['id' => $listingId]);
            }

            redirect('marketplace/index');
        }

        return View::render('marketplace/show', [
            'pageTitle' => 'Listing Workspace',
            'listing' => $listing,
            'bids' => $isOwner || $isAdmin ? $this->bids->forSellerListing($listingId, (int) $listing['seller_id']) : [],
            'selectedBid' => $selectedBid,
            'canManage' => $isOwner || $isAdmin,
            'isSelectedBuyer' => $isSelectedBuyer,
        ]);
    }

    public function view(): string
    {
        Auth::requireRole(['client', 'tasker', 'admin']);

        $listingId = (int) ($_GET['id'] ?? 0);

        if ($listingId <= 0) {
            Session::flash('error', 'Listing not found.');
            redirect('marketplace/index');
        }

        $listing = $this->listings->findById($listingId);

        if ($listing === null) {
            Session::flash('error', 'Listing not found.');
            redirect('marketplace/index');
        }

        if ((int) $listing['seller_id'] === (int) Auth::id()) {
            redirect('marketplace/show', ['id' => $listingId]);
        }

        $selectedBid = $this->bids->findSelectedForListing($listingId);
        $existingBid = $this->bids->findForBuyer($listingId, (int) Auth::id());
        $isSelectedBuyer = $selectedBid !== null && (int) $selectedBid['buyer_id'] === (int) Auth::id();

        if ((string) $listing['status'] !== 'open' || (int) $listing['is_active'] !== 1) {
            if ($isSelectedBuyer || Auth::role() === 'admin') {
                redirect('marketplace/show', ['id' => $listingId]);
            }

            Session::flash('error', 'That listing is no longer available for bidding.');
            redirect('marketplace/index');
        }

        return View::render('marketplace/view', [
            'pageTitle' => 'Marketplace Listing',
            'listing' => $listing,
            'existingBid' => $existingBid,
            'selectedBid' => $selectedBid,
            'errors' => [],
            'fieldErrors' => [],
        ]);
    }
}
