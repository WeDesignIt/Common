<?php

namespace WeDesignIt\Common\Http\Traits;

trait UsesCustomHeaders
{
    protected array $customHeaders = []; // e.g. ['X-Api-Key' => 'value', 'X-Company-Id' => 'value']

    protected function prepareHeaders(array $headers): array
    {
        return array_merge($headers, $this->customHeaders);
    }
}
