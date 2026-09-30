<?php
namespace Ometra\HelaSdk\Tests\Feature;

use Ometra\HelaSdk\Dtos\ServiceDto;
use Ometra\HelaSdk\Tests\TestCase;

final class ServiceDisplayDtoTest extends TestCase
{
    public function test_display_field_is_additive_and_older_responses_still_work(): void
    {
        $display = ['offer_id' => '99', 'offer_name' => 'Visible', 'service_type_code' => 'ANT'];
        $dto = ServiceDto::from(['offer_id' => '55', 'service_type_code' => 'PRE', 'zephyr_display' => $display]);
        $this->assertSame($display, $dto->zephyrDisplay);
        $this->assertSame('55', $dto->offerId);
        $this->assertSame('PRE', $dto->serviceTypeCode);
        $this->assertSame($display, $dto->attributes['zephyr_display']);
        $this->assertNull(ServiceDto::from(['offer_id' => '55'])->zephyrDisplay);
        $this->assertSame($display, ServiceDto::from($dto->toArray())->zephyrDisplay);
    }
}
