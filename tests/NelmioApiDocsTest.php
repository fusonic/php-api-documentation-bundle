<?php

namespace Fusonic\ApiDocumentationBundle\Tests;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class NelmioApiDocsTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
    }

    /**
     * Write the NelmioApiDoc json output to a file for manual testing
     * with the editor: https://editor.swagger.io/.
     */
    private function writeApiDocsJson(string $content): void
    {
        file_put_contents(__DIR__.'/../var/open_api_result.json', $content);
    }

    public function testGetJsonDocs(): void
    {
        $this->client->request('GET', '/docs/default.json');
        $response = $this->client->getResponse();

        $this->writeApiDocsJson((string) $response->getContent());

        $content = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertResponseStatusCodeSame(200);
        self::assertArrayHasKey('paths', $content);

        $this->verifyManualOutputRoute('/test-manual-output/{id}', $content);
        $this->verifyReturnTypeRoute('/test-return-type/{id}', $content);
        $this->verifyBuiltinReturnTypeRoute('/test-builtin-return-type/{id}', $content);
        $this->verifyAnnotationBuiltinArrayReturnTypeRoute('/annotation-builtin-type-array/{id}', $content);
        $this->verifyAnnotationCustomArrayReturnTypeRoute('/test-annotation-custom-return-type/{id}', $content);
        $this->verifyPostRouteWithTag('/test-post-route-with-tag/{id}', $content);
        $this->verifyCombinedAttributesRoute('/test-combined-attributes/{id}', $content);
        $this->verifyManualCollectionOutputRoute('/test-manual-collection-output/{id}', $content);

        self::assertArrayHasKey('components', $content);
        self::assertEquals([
            'schemas' => [
                'TestRequest' => [
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                        ],
                    ],
                    'type' => 'object',
                ],
                'TestResponse' => [
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                        ],
                    ],
                    'type' => 'object',
                ],
            ],
        ], $content['components']);
    }

    private function verifyManualOutputRoute(string $path, array $content): void
    {
        $this->verifyTestRequestObjectQuery($path, $content);

        self::assertArrayHasKey('responses', $content['paths'][$path]['get']);
        self::assertCount(1, $content['paths'][$path]['get']['responses']);

        self::assertEquals([
            200 => [
                'description' => 'get TestResponse',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            '$ref' => '#/components/schemas/TestResponse',
                        ],
                    ],
                ],
            ],
        ], $content['paths'][$path]['get']['responses']);
    }

    private function verifyManualCollectionOutputRoute(string $path, array $content): void
    {
        $this->verifyTestRequestObjectQuery($path, $content);
        self::assertArrayHasKey('tags', $content['paths'][$path]['get']);
        self::assertEquals([
            'test',
        ], $content['paths'][$path]['get']['tags']);
        self::assertCount(1, $content['paths'][$path]);
        self::assertArrayHasKey('responses', $content['paths'][$path]['get']);
        self::assertCount(1, $content['paths'][$path]['get']['responses']);

        self::assertEquals([
            200 => [
                'description' => 'get TestResponse collection',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'array',
                            'items' => [
                                '$ref' => '#/components/schemas/TestResponse',
                            ],
                        ],
                    ],
                ],
            ],
        ], $content['paths'][$path]['get']['responses']);
    }

    private function verifyReturnTypeRoute(string $path, array $content): void
    {
        $this->verifyTestRequestObjectQuery($path, $content);

        self::assertArrayHasKey('responses', $content['paths'][$path]['get']);
        self::assertCount(1, $content['paths'][$path]['get']['responses']);

        self::assertEquals([
            200 => [
                'description' => 'get TestResponse',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            '$ref' => '#/components/schemas/TestResponse',
                        ],
                    ],
                ],
            ],
        ], $content['paths']['/test-return-type/{id}']['get']['responses']);
    }

    private function verifyBuiltinReturnTypeRoute(string $path, array $content): void
    {
        $this->verifyTestRequestObjectQuery($path, $content);

        self::assertArrayHasKey('responses', $content['paths'][$path]['get']);
        self::assertCount(1, $content['paths'][$path]['get']['responses']);

        self::assertEquals([
            200 => [
                'description' => 'get string',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'string',
                        ],
                    ],
                ],
            ],
        ], $content['paths'][$path]['get']['responses']);
    }

    private function verifyAnnotationBuiltinArrayReturnTypeRoute(string $path, array $content): void
    {
        $this->verifyTestRequestObjectQuery($path, $content);

        self::assertArrayHasKey('responses', $content['paths'][$path]['get']);
        self::assertCount(1, $content['paths'][$path]['get']['responses']);

        self::assertEquals([
            200 => [
                'description' => 'get string collection',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                ],
            ],
        ], $content['paths'][$path]['get']['responses']);
    }

    private function verifyAnnotationCustomArrayReturnTypeRoute(string $path, array $content): void
    {
        $this->verifyTestRequestObjectQuery($path, $content);

        self::assertArrayHasKey('responses', $content['paths'][$path]['get']);
        self::assertCount(1, $content['paths'][$path]['get']['responses']);

        self::assertEquals([
            200 => [
                'description' => 'get TestResponse collection',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'array',
                            'items' => [
                                '$ref' => '#/components/schemas/TestResponse',
                            ],
                        ],
                    ],
                ],
            ],
        ], $content['paths'][$path]['get']['responses']);
    }

    private function verifyTestRequestObjectQuery(string $path, array $content): void
    {
        self::assertArrayHasKey('get', $content['paths'][$path]);
        self::assertArrayHasKey('parameters', $content['paths'][$path]['get']);
        self::assertCount(2, $content['paths'][$path]['get']['parameters']);

        self::assertEquals([
            'name' => 'TestRequest',
            'in' => 'query',
            'explode' => true,
            'schema' => [
                '$ref' => '#/components/schemas/TestRequest',
            ],
        ], $content['paths'][$path]['get']['parameters'][0]);

        self::assertEquals([
            'name' => 'id',
            'in' => 'path',
            'required' => true,
            'schema' => [
                'type' => 'string',
            ],
        ], $content['paths'][$path]['get']['parameters'][1]);
    }

    private function verifyTestRequestObjectBody(string $path, array $content): void
    {
        self::assertArrayHasKey('post', $content['paths'][$path]);
        self::assertArrayHasKey('parameters', $content['paths'][$path]['post']);
        self::assertCount(1, $content['paths'][$path]['post']['parameters']);
        self::assertEquals([
            'name' => 'id',
            'in' => 'path',
            'required' => true,
            'schema' => [
                'type' => 'string',
            ],
        ], $content['paths'][$path]['post']['parameters'][0]);

        self::assertArrayHasKey('requestBody', $content['paths'][$path]['post']);
        self::assertCount(2, $content['paths'][$path]['post']['requestBody']);

        self::assertEquals(
            [
                'required' => true,
                'content' => [
                        'application/json' => [
                                'schema' => [
                                        '$ref' => '#/components/schemas/TestRequest',
                                    ],
                            ],
                    ],
            ],
            $content['paths'][$path]['post']['requestBody']
        );
    }

    private function verifyPostRouteWithTag(string $path, array $content): void
    {
        $this->verifyTestRequestObjectBody($path, $content);

        self::assertArrayHasKey('responses', $content['paths'][$path]['post']);
        self::assertCount(1, $content['paths'][$path]['post']['responses']);
        self::assertArrayHasKey('tags', $content['paths'][$path]['post']);
        self::assertEquals([
            'test',
        ], $content['paths'][$path]['post']['tags']);
        self::assertCount(1, $content['paths'][$path]);

        self::assertEquals([
            200 => [
                'description' => 'post TestResponse',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            '$ref' => '#/components/schemas/TestResponse',
                        ],
                    ],
                ],
            ],
        ], $content['paths'][$path]['post']['responses']);
    }

    private function verifyCombinedAttributesRoute(string $path, array $content): void
    {
        $this->verifyTestRequestObjectBody($path, $content);

        self::assertArrayHasKey('responses', $content['paths'][$path]['post']);
        self::assertCount(2, $content['paths'][$path]['post']['responses']);
        self::assertCount(1, $content['paths'][$path]);

        self::assertEquals([
            200 => [
                'description' => 'post TestResponse',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            '$ref' => '#/components/schemas/TestResponse',
                        ],
                    ],
                ],
            ],
            404 => [
                'description' => 'Object was not found.',
            ],
        ], $content['paths'][$path]['post']['responses']);
    }
}
