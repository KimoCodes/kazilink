<?php

declare(strict_types=1);

final class MarketplaceBidController
{
    private ProductListing $listings;
    private ProductBid $bids;

    public function __construct()
    {
        $this->listings = new ProductListing();
        $this->bids = new ProductBid();
    }

    public function create(): string
    {
        Auth::requireRole(['client', 'tasker', 'admin']);
        SubscriptionAccess::requirePaidAccess('Placing marketplace offers requires an active subscription after your free trial.');
        verifyPostRequest('marketplace/index');

        $listingId = (int) ($_POST['listing_id'] ?? 0);

        if ($listingId <= 0) {
            Session::flash('error', 'Listing not found.');
            redirect('marketplace/index');
        }

        $listing = $this->listings->findOpenById($listingId);

        if ($listing === null) {
            Session::flash('error', 'That listing is no longer open for bids.');
            redirect('marketplace/index');
        }

        if ((int) $listing['seller_id'] === (int) Auth::id()) {
            Session::flash('error', 'You cannot bid on your own listing.');
            redirect('marketplace/view', ['id' => $listingId]);
        }

        if ($this->bids->findForBuyer($listingId, (int) Auth::id()) !== null) {
            Session::flash('error', 'You have already placed a bid on this listing.');
            redirect('marketplace/view', ['id' => $listingId]);
        }

        $input = Validator::trim($_POST);
        Session::setOldInput([
            'marketplace_bid_amount' => (string) ($input['amount'] ?? ''),
            'marketplace_bid_message' => (string) ($input['message'] ?? ''),
        ]);
        $fieldErrors = Validator::marketplaceBidFields($input, (float) $listing['starting_price']);

        if ($fieldErrors !== []) {
            return View::render('marketplace/view', [
                'pageTitle' => 'Marketplace Listing',
                'listing' => $listing,
                'existingBid' => null,
                'selectedBid' => null,
                'errors' => Validator::flattenFieldErrors($fieldErrors),
                'fieldErrors' => $fieldErrors,
            ]);
        }

        try {
            $this->bids->createForBuyerOnOpenListing([
                'listing_id' => $listingId,
                'buyer_id' => (int) Auth::id(),
                'amount' => number_format((float) $input['amount'], 2, '.', ''),
                'message' => (string) ($input['message'] ?? ''),
            ]);
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
            redirect('marketplace/view', ['id' => $listingId]);
        }

        Session::clearOldInput();
        Session::flash('success', 'Your marketplace bid has been submitted.');
        redirect('marketplace/view', ['id' => $listingId]);
    }

    public function select(): string
    {
        Auth::requireRole(['client', 'tasker', 'admin']);
        verifyPostRequest('marketplace/my-listings');

        $bidId = (int) ($_POST['bid_id'] ?? 0);

        if ($bidId <= 0) {
            Session::flash('error', 'Bid not found.');
            redirect('marketplace/my-listings');
        }

        $bid = $this->bids->findSelectableForSeller($bidId, (int) Auth::id());

        if ($bid === null) {
            Session::flash('error', 'Bid not found.');
            redirect('marketplace/my-listings');
        }

        if ((int) $bid['listing_is_active'] !== 1 || (string) $bid['listing_status'] !== 'open' || (string) $bid['status'] !== 'pending') {
            Session::flash('error', 'Only pending bids on active open listings can be selected.');
            redirect('marketplace/show', ['id' => (int) $bid['listing_id']]);
        }

        try {
            $this->bids->selectHighestPendingBidForSeller($bidId, (int) Auth::id());
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
            redirect('marketplace/show', ['id' => (int) $bid['listing_id']]);
        }

        Session::flash('success', 'Buyer selected. Contact information is now visible to both sides.');
        redirect('marketplace/show', ['id' => (int) $bid['listing_id']]);
    }
}
