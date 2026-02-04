<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

declare(strict_types=1);

namespace Tobento\App\Crud\Test\Feature\Testing;

use PHPUnit\Framework\ExpectationFailedException;
use Tobento\App\AppInterface;
use Tobento\App\Crud\Testing\AbstractCrudTestCase;
use Tobento\App\Crud\Boot\Crud;
use Tobento\Service\Seeder\SeedInterface;
use Tobento\Service\Seeder\Lorem;
use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\Language\LanguageFactory;
use Tobento\Service\Language\LanguagesInterface;
use Tobento\Service\Language\Languages;

class AbstractCrudTestCaseTest extends AbstractCrudTestCase
{
    use \Tobento\App\Testing\Database\RefreshDatabases;
    use \Tobento\App\Testing\Http\RefreshSession;
    
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/../../..');
        $app->boot(Crud::class);
        $app->boot(\Tobento\App\User\Boot\User::class);
        $app->boot(App\UserCrudBoot::class);
        
        $app->on(LanguagesInterface::class, function() {
            $languageFactory = new LanguageFactory();
            return new Languages(
                $languageFactory->createLanguage(locale: 'en', default: true),
                $languageFactory->createLanguage(locale: 'de', slug: 'de'),
            );
        });
        
        return $app;
    }
    
    protected function getCrudController(): string
    {
        return App\UserCrudController::class;
    }
    
    public function testRequestUris()
    {
        $this->bootingApp();
        
        $this->assertSame('users', $this->generateIndexUri());
        $this->assertSame('de/users', $this->generateIndexUri(locale: 'de'));
        $this->assertSame('users/bulk/bulk-edit', $this->generateBulkUri(action: 'bulk-edit'));
        $this->assertSame('de/users/bulk/bulk-edit', $this->generateBulkUri(action: 'bulk-edit', locale: 'de'));
        $this->assertSame('users/create', $this->generateCreateUri());
        $this->assertSame('de/users/create', $this->generateCreateUri(locale: 'de'));
        $this->assertSame('users', $this->generateStoreUri());
        $this->assertSame('de/users', $this->generateStoreUri(locale: 'de'));
        $this->assertSame('users/2/edit', $this->generateEditUri(id: 2));
        $this->assertSame('de/users/foo/edit', $this->generateEditUri(id: 'foo', locale: 'de'));
        $this->assertSame('users/2', $this->generateUpdateUri(id: 2));
        $this->assertSame('de/users/foo', $this->generateUpdateUri(id: 'foo', locale: 'de'));
        $this->assertSame('users/2', $this->generateDeleteUri(id: 2));
        $this->assertSame('de/users/foo', $this->generateDeleteUri(id: 'foo', locale: 'de'));
        $this->assertSame('users/2', $this->generateShowUri(id: 2));
        $this->assertSame('de/users/foo', $this->generateShowUri(id: 'foo', locale: 'de'));
    }
    
    public function testGetSeedFactoryWithReplaces()
    {
        $this->bootingApp();
        
        $user = $this->getSeedFactory(['type' => 'business'])->makeOne();
        
        $this->assertSame('business', $user->type());
    }
    
    public function testAssertCrudIndexEntityCount()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $this->getSeedFactory()->times(5)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudIndexEntityCount(5);
    }
    
    public function testAssertCrudIndexEntityCountThrowsExceptionIfInvalidCount()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('There should be a total of 5 records found in the index table.');
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudIndexEntityCount(5);
    }
    
    public function testAssertCrudIndexEntityCountThrowsExceptionIfFound()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('There should be no records found in the index table.');
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $this->getSeedFactory()->times(1)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudIndexEntityCount(0);
    }
    
    public function testAssertCrudIndexEntityCountUsesCustomMessage()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('custom message');
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $this->getSeedFactory()->times(1)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudIndexEntityCount(0, 'custom message');
    }
    
    public function testAssertCrudIndexEntityExists()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $this->getSeedFactory()->times(5)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudIndexEntityExists(entityId: 3, withButtons: ['edit', 'delete'], withoutButtons: []);
    }
    
    public function testAssertCrudIndexEntityExistsThrowsExceptionIfNotExists()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Entity id 2 not found in the index table.');
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $this->getSeedFactory()->times(1)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudIndexEntityExists(entityId: 2);
    }
    
    public function testAssertCrudIndexEntityExistsUsesCustomMessage()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('custom message');
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $this->getSeedFactory()->times(1)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudIndexEntityExists(entityId: 2, message: 'custom message');
    }
    
    public function testAssertCrudIndexEntityExistsWithButtons()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $this->getSeedFactory()->times(3)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudIndexEntityExists(entityId: 2, withButtons: ['edit', 'delete'], withoutButtons: []);
    }
    
    public function testAssertCrudIndexEntityExistsWithButtonsThrowsExceptionIfButtonNotExists()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Entity id 2 misses the button(s) "foo" in the index table.');
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $this->getSeedFactory()->times(3)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudIndexEntityExists(entityId: 2, withButtons: ['edit', 'foo']);
    }
    
    public function testAssertCrudIndexEntityExistsWithoutButtons()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $this->getSeedFactory()->times(3)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudIndexEntityExists(entityId: 2, withoutButtons: ['foo']);
    }
    
    public function testAssertCrudIndexEntityExistsWithoutButtonsThrowsExceptionIfButtonExists()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Entity id 2 has the unexpected button(s) "edit" in the index table.');
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $this->getSeedFactory()->times(3)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudIndexEntityExists(entityId: 2, withoutButtons: ['edit']);
    }
    
    public function testAssertCrudIndexEntityMissing()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $this->getSeedFactory()->times(1)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudIndexEntityMissing(entityId: 3);
    }
    
    public function testAssertCrudIndexEntityMissingThrowsExceptionIfExists()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The index table has unexpected entity id 1.');
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $this->getSeedFactory()->times(2)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudIndexEntityMissing(entityId: 1);
    }
    
    public function testAssertCrudIndexEntityMissingUsesCustomMessage()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('custom message');
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $this->getSeedFactory()->times(2)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudIndexEntityMissing(entityId: 1, message: 'custom message');
    }
    
    public function testAssertCrudIndexHeaderColumnsExists()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudIndexHeaderColumnsExists(columns: ['username', 'actions']);
    }
    
    public function testAssertCrudIndexHeaderColumnsExistsThrowsExceptionIfColumnNotExists()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Header column missing not found in the index table.');
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudIndexHeaderColumnsExists(columns: ['missing']);
    }
    
    public function testAssertCrudIndexHeaderColumnsExistsUsesCustomMessage()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('custom message');
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudIndexHeaderColumnsExists(columns: ['missing'], message: 'custom message');
    }

    public function testAssertCrudIndexHeaderColumnsMissing()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudIndexHeaderColumnsMissing(columns: ['foo']);
    }
    
    public function testAssertCrudIndexHeaderColumnsMissingThrowsExceptionIfColumnExists()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Header column actions has been found in the index table.');
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudIndexHeaderColumnsMissing(columns: ['actions']);
    }
    
    public function testAssertCrudIndexHeaderColumnsMissingUsesCustomMessage()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('custom message');
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudIndexHeaderColumnsMissing(columns: ['actions'], message: 'custom message');
    }
    
    public function testAssertCrudIndexFiltersExists()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudIndexFiltersExists(filters: ['category'], group: 'header');
    }
    
    public function testAssertCrudIndexFiltersExistsInFooterGroup()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudIndexFiltersExists(filters: ['pagination_items_footer'], group: 'footer');
    }
    
    public function testAssertCrudIndexFiltersExistsThrowsExceptionIfFilterNotExists()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The index table misses the header filter(s) "foo, bar".');
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudIndexFiltersExists(filters: ['foo', 'bar'], group: 'header');
    }
    
    public function testAssertCrudIndexFiltersExistsUsesCustomMessage()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Custom message');
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudIndexFiltersExists(filters: ['foo', 'bar'], group: 'header', message: 'Custom message');
    }
    
    public function testAssertCrudIndexFiltersMissing()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudIndexFiltersMissing(filters: ['foo'], group: 'header');
    }
    
    public function testAssertCrudIndexFiltersMissingThrowsExceptionIfFilterExists()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The index table has the unexpected header filter(s) "columns".');
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudIndexFiltersMissing(filters: ['columns'], group: 'header');
    }
    
    public function testAssertCrudIndexFiltersMissingUsesCustomMessage()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Custom message');
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudIndexFiltersMissing(filters: ['columns'], group: 'header', message: 'Custom message');
    }
    
    public function testAssertCrudIndexButtonsExists()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudIndexButtonsExists(buttons: ['create'], group: 'global');
    }
    
    public function testAssertCrudIndexButtonsExistsForEntityGroup()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $this->getSeedFactory()->times(1)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudIndexButtonsExists(buttons: ['edit'], group: 'entity');
    }
    
    public function testAssertCrudIndexButtonsExistsThrowsExceptionIfButtonNotExists()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The index table misses the global button(s) "foo, bar".');
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudIndexButtonsExists(buttons: ['foo', 'bar'], group: 'global');
    }
    
    public function testAssertCrudIndexButtonsExistsUsesCustomMessage()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Custom message');
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudIndexButtonsExists(buttons: ['foo', 'bar'], group: 'global', message: 'Custom message');
    }
    
    public function testAssertCrudIndexButtonsMissing()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudIndexButtonsMissing(buttons: ['foo'], group: 'global');
    }
    
    public function testAssertCrudIndexButtonsMissingThrowsExceptionIfButtonExists()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The index table has the unexpected global button(s) "create".');
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudIndexButtonsMissing(buttons: ['create'], group: 'global');
    }
    
    public function testAssertCrudIndexButtonsMissingUsesCustomMessage()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Custom message');
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudIndexButtonsMissing(buttons: ['create'], group: 'global', message: 'Custom message');
    }
    
    public function testAssertCrudIndexBulkActionsExists()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudIndexBulkActionsExists(actions: ['bulk-edit']);
    }
    
    public function testAssertCrudIndexBulkActionsExistsThrowsExceptionIfActionNotExists()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The index table misses the bulk action(s) "foo, bar".');
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudIndexBulkActionsExists(actions: ['foo', 'bar']);
    }
    
    public function testAssertCrudIndexBulkActionsExistsUsesCustomMessage()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Custom message');
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudIndexBulkActionsExists(actions: ['foo', 'bar'], message: 'Custom message');
    }
    
    public function testAssertCrudIndexBulkActionsMissing()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudIndexBulkActionsMissing(actions: ['foo']);
    }
    
    public function testAssertCrudIndexBulkActionsMissingThrowsExceptionIfActionExists()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The index table has the unexpected bulk action(s) "bulk-edit".');
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudIndexBulkActionsMissing(actions: ['bulk-edit']);
    }
    
    public function testAssertCrudIndexBulkActionsMissingUsesCustomMessage()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Custom message');
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudIndexBulkActionsMissing(actions: ['bulk-edit'], message: 'Custom message');
    }
    
    public function testAssertCrudFormFieldExists()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $this->getSeedFactory()->times(2)->create();

        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'username');
    }
    
    public function testAssertCrudFormFieldExistsThrowsIfNotExists()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The field inexistence is missing in the form.');
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $this->getSeedFactory()->times(2)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'inexistence');
    }
    
    public function testAssertCrudFormFieldExistsUsesCustomMessage()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Custom message');
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $this->getSeedFactory()->times(2)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'inexistence', message: 'Custom message');
    }
    
    public function testAssertCrudFormFieldExistsWithLabel()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $this->getSeedFactory()->times(2)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'username', label: 'Username');
    }
    
    public function testAssertCrudFormFieldExistsWithLabelThrowsIfIncorrect()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The field username has not the correct label "Incorrect" in the form.');
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $this->getSeedFactory()->times(2)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'username', label: 'Incorrect');
    }
    
    public function testAssertCrudFormFieldExistsWithRequiredText()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $this->getSeedFactory()->times(2)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'smartphone', requiredText: 'Required smartphone ...');
    }
    
    public function testAssertCrudFormFieldExistsWithRequiredTextThrowsIfIncorrect()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The field smartphone has not the correct required text "Incorrect" in the form.');
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $this->getSeedFactory()->times(2)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'smartphone', requiredText: 'Incorrect');
    }
    
    public function testAssertCrudFormFieldExistsWithOptionalText()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $this->getSeedFactory()->times(2)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'username', optionalText: 'optional');
    }
    
    public function testAssertCrudFormFieldExistsWithOptionalTextThrowsIfIncorrect()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The field username has not the correct optional text "Incorrect" in the form.');
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $this->getSeedFactory()->times(2)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'username', optionalText: 'Incorrect');
    }
    
    public function testAssertCrudFormFieldExistsWithInfoText()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $this->getSeedFactory()->times(2)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'email', infoText: 'Email info ...');
    }
    
    public function testAssertCrudFormFieldExistsWithInfoTextThrowsIfIncorrect()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The field email has not the correct info text "Incorrect" in the form.');
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $this->getSeedFactory()->times(2)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'email', infoText: 'Incorrect');
    }
    
    public function testAssertCrudFormFieldExistsWithErrorText()
    {
        $http = $this->fakeHttp();
        $http->previousUri($this->generateCreateUri());
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'email' => 'tom@example.com',
            //'smartphone' => '555', // is required
        ]);
                
        $http->followRedirects()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'smartphone', errorText: 'The smartphone is required.');
    }
    
    public function testAssertCrudFormFieldExistsWithErrorTextThrowsIfIncorrect()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The field smartphone has not the correct error text "Incorrect" in the form.');
        
        $http = $this->fakeHttp();
        $http->previousUri($this->generateCreateUri());
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'email' => 'tom@example.com',
            //'smartphone' => '555', // is required
        ]);
                
        $http->followRedirects()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'smartphone', errorText: 'Incorrect');
    }
    
    public function testAssertCrudFormFieldExistsWithTranslatable()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $this->getSeedFactory()->times(2)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'translatable', label: 'Translatable', translatable: true);
    }
    
    public function testAssertCrudFormFieldExistsWithTranslatableThrowsIfNotTranslatable()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The field username is not translatable in the form.');
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $this->getSeedFactory()->times(2)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'username', translatable: true);
    }
    
    public function testAssertCrudFormFieldExistsWithTranslatableNotDefinedAsTransalableShouldBeValid()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $this->getSeedFactory()->times(2)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'translatable', label: 'Translatable');
    }
    
    public function testAssertCrudFormFieldExistsWithTranslatableNotDefinedAsTranslatableThrows()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The field translatable is translatable in the form.');
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $this->getSeedFactory()->times(2)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'translatable', label: 'Translatable', translatable: false);
    }

    public function testAssertCrudFormFieldExistsWithTranslatableAndLabel()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $this->getSeedFactory()->times(2)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(
                field: 'translatable',
                translatable: true,
                label: 'Translatable',
            );
    }
    
    public function testAssertCrudFormFieldExistsWithTranslatableAndOptionalText()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $this->getSeedFactory()->times(2)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(
                field: 'translatable',
                translatable: true,
                optionalText: 'Optional translatable ...',
            );
    }
    
    public function testAssertCrudFormFieldExistsWithTranslatableAndInfoText()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $this->getSeedFactory()->times(2)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(
                field: 'translatable',
                translatable: true,
                infoText: 'Translatable info text ...',
            );
    }
    
    public function testAssertCrudFormFieldExistsWithTranslatableAndErrorText()
    {
        $http = $this->fakeHttp();
        $http->previousUri($this->generateCreateUri());
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'email' => 'tom@example.com',
            'smartphone' => '555', // is required
            'translatable' => ['en' => 'foo%', 'de' => 'bar%'], // alnum
        ]);
        
        $http->followRedirects()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(
                field: 'translatable',
                translatable: true,
                errorText: 'The translatable.en must only contain letters [a-zA-Z] and numbers.',
            );
    }
    
    public function testAssertCrudFormFieldMissing()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $this->getSeedFactory()->times(2)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldMissing(field: 'foo');
    }
    
    public function testAssertCrudFormFieldMissingThrowsExceptionIfExists()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('The field username is found in the form.');
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateCreateUri());
        
        $this->getSeedFactory()->times(2)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldMissing(field: 'username');
    }
    
    public function testAssertCrudFormFieldMissingUsesCustomMessage()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Custom message');
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateCreateUri());
        
        $this->getSeedFactory()->times(2)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldMissing(field: 'username', message: 'Custom message');
    }
    
    public function testFilterAction()
    {
        $http = $this->fakeHttp();
        $http->request(
            method: 'GET',
            uri: $this->generateIndexUri(),
            query: ['filter' => ['field' => ['type' => 'business']]],
        );
        
        $this->getSeedFactory(['type' => 'private'])->times(1)->create();
        $this->getSeedFactory(['type' => 'business'])->times(2)->create();
        
        /*$http->response()
            ->assertStatus(302)
            //->assertHasSession(key: 'crudFilters.users.id', value: '2')
            //->assertLocation($this->generateIndexUri());
            ->assertRedirectToRoute(name: 'users.index', parameters: []);*/
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudIndexEntityCount(2);
    }
    
    public function testStoreAction()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'email' => 'tom@example.com',
            'smartphone' => '555', // is required
        ]);
        
        $http->response()
            ->assertStatus(302)
            ->assertLocation($this->generateIndexUri());
        
        $http->followRedirects()
            ->assertStatus(200)
            ->assertCrudIndexEntityCount(1);
        
        $this->assertSame(1, $this->getCrudRepository()->count());
    }
    
    public function testStoreActionWithValidationErrors()
    {
        $http = $this->fakeHttp();
        $http->previousUri($this->generateCreateUri());
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'email' => 'tom@example.com',
            //'smartphone' => '555', // is required
            'translatable' => ['en' => 'foo%', 'de' => 'bar%'],
        ]);
        
        $http->response()
            ->assertStatus(302)
            ->assertLocation($this->generateCreateUri());
                
        $http->followRedirects()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'smartphone', errorText: 'The smartphone is required.')
            ->assertCrudFormFieldExists(
                field: 'translatable',
                //translatable: true,
                errorText: 'The translatable.en must only contain letters [a-zA-Z] and numbers.',
            );
        
        $this->assertSame(0, $this->getCrudRepository()->count());
    }
    
    public function testUpdateAction()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'PATCH', uri: $this->generateUpdateUri(id: 1))->body([
            'smartphone' => '555',
        ]);
        
        $this->getSeedFactory()->times(2)->create();
        
        $http->response()
            ->assertStatus(302)
            ->assertLocation($this->generateIndexUri());
        
        $http->followRedirects()
            ->assertStatus(200)
            ->assertCrudIndexEntityCount(2);
        
        $this->assertSame('555', $this->getCrudRepository()->findById(1)->smartphone());
    }
    
    public function testUpdateActionWithValidationErrors()
    {
        $http = $this->fakeHttp();
        $http->previousUri($this->generateEditUri(id: 1));
        $http->request(method: 'PATCH', uri: $this->generateUpdateUri(id: 1))->body([
            'smartphone' => ['invalid'],
        ]);
        
        $this->getSeedFactory()->times(2)->create();
        
        $http->response()
            ->assertStatus(302)
            ->assertLocation($this->generateEditUri(id: 1));
        
        $http->followRedirects()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'smartphone', errorText: 'The smartphone must be a string.');
    }
    
    public function testDeleteAction()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'DELETE', uri: $this->generateDeleteUri(id: 1));
        
        $this->getSeedFactory()->times(2)->create();

        $http->response()
            ->assertStatus(302)
            ->assertLocation($this->generateIndexUri());
        
        $http->followRedirects()
            ->assertStatus(200)
            ->assertCrudIndexEntityCount(1);
        
        $this->assertSame(1, $this->getCrudRepository()->count());
    }
    
    public function testBulkEditAction()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: $this->generateBulkUri(action: 'bulk-edit'))->body([
            'ids' => [2, 3],
            'smartphone' => '555',
        ]);
        
        $this->getSeedFactory()->times(5)->create();

        $http->response()
            ->assertStatus(302)
            ->assertLocation($this->generateIndexUri());
        
        $http->followRedirects()
            ->assertStatus(200)
            ->assertCrudIndexEntityCount(5);
        
        $this->assertNotSame('555', $this->getCrudRepository()->findById(1)->smartphone());
        $this->assertSame('555', $this->getCrudRepository()->findById(2)->smartphone());
        $this->assertSame('555', $this->getCrudRepository()->findById(3)->smartphone());
    }
    
    public function testBulkEditActionWithValidationErrors()
    {
        $http = $this->fakeHttp();
        $http->previousUri($this->generateIndexUri());
        $http->request(method: 'POST', uri: $this->generateBulkUri(action: 'bulk-edit'))->body([
            'ids' => [2, 3],
            'smartphone' => ['555'],
        ]);
        
        $this->getSeedFactory()->times(5)->create();
        
        $http->response()
            ->assertStatus(302)
            ->assertLocation($this->generateIndexUri());
        
        $http->followRedirects()
            ->assertStatus(200)
            ->assertCrudIndexEntityCount(5)
            ->assertCrudFormFieldExists(field: 'smartphone', errorText: 'The smartphone must be a string.');
    }
    
    public function testBulkDeleteAction()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: $this->generateBulkUri(action: 'bulk-delete'))->body([
            'ids' => [2, 3],
        ]);

        $this->getSeedFactory()->times(5)->create();

        $http->response()
            ->assertStatus(302)
            ->assertLocation($this->generateIndexUri());

        $http->followRedirects()
            ->assertStatus(200)
            ->assertCrudIndexEntityCount(3);

        $this->assertNotNull($this->getCrudRepository()->findById(1));
        $this->assertNull($this->getCrudRepository()->findById(2));
        $this->assertNull($this->getCrudRepository()->findById(3));
    }
}