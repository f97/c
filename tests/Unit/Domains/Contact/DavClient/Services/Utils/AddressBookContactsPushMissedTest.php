<?php

namespace Tests\Unit\Domains\Contact\DavClient\Services\Utils;

use App\Domains\Contact\Dav\Web\Backend\CardDAV\CardDAVBackend;
use App\Domains\Contact\DavClient\Jobs\PushVCard;
use App\Domains\Contact\DavClient\Services\Utils\AddressBookContactsPushMissed;
use App\Domains\Contact\DavClient\Services\Utils\Model\ContactDto;
use App\Domains\Contact\DavClient\Services\Utils\Model\ContactPushDto;
use App\Models\AddressBookSubscription;
use App\Models\Contact;
use App\Models\SyncToken;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Unit\Domains\Contact\DAV\CardEtag;

class AddressBookContactsPushMissedTest extends TestCase
{
    use DatabaseTransactions;
    use CardEtag;

    /** @test */
    public function it_push_contacts_missed()
    {
        $subscription = AddressBookSubscription::factory()->create();
        $this->setPermissionInVault($subscription->user, Vault::PERMISSION_MANAGE, $subscription->vault);
        $token = SyncToken::factory()->create([
            'account_id' => $subscription->user->account_id,
            'user_id' => $subscription->user_id,
            'name' => 'contacts1',
            'timestamp' => now()->addDays(-1),
        ]);
        $subscription->localSyncToken = $token->id;
        $subscription->save();

        $contact = Contact::factory()->create([
            'vault_id' => $subscription->vault_id,
            'first_name' => 'Test',
            'id' => 'affacde9-b2fe-4371-9acb-6612aaee6971',
        ]);
        $card = $this->getCard($contact);
        $etag = $this->getEtag($contact, true);

        $this->mock(CardDAVBackend::class, function (MockInterface $mock) use ($card, $etag, $contact) {
            $mock->shouldReceive('withUser')->andReturn($mock);
            $mock->shouldReceive('getUuid')
                ->once()
                ->withArgs(function ($uri) {
                    $this->assertEquals('uuid6', $uri);

                    return true;
                })
                ->andReturn('uuid3');
            $mock->shouldReceive('prepareCard')
                ->once()
                ->withArgs(function ($c) use ($contact) {
                    $this->assertEquals($contact, $c);

                    return true;
                })
                ->andReturn([
                    'account_id' => $contact->account_id,
                    'contact_id' => $contact->id,
                    'carddata' => $card,
                    'uri' => 'uuid3',
                    'etag' => $etag,
                ]);
        });

        $batchs = (new AddressBookContactsPushMissed)
            ->withSubscription($subscription)
            ->execute([], collect([
                'uuid6' => new ContactDto('uuid6', $etag),
            ]), collect([$contact]));

        $this->assertCount(1, $batchs);
        $batch = $batchs->first();
        $this->assertInstanceOf(PushVCard::class, $batch);
        $dto = $this->getPrivateValue($batch, 'contact');
        $this->assertInstanceOf(ContactPushDto::class, $dto);
        $this->assertEquals('uuid3', $dto->uri);
        $this->assertEquals(2, $dto->mode);
    }
}