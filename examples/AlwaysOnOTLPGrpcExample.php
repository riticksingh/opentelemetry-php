<?php

declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';

use OpenTelemetry\Contrib\OtlpGrpc\Exporter as OTLPExporter;
use OpenTelemetry\Sdk\Trace\Attributes;
use OpenTelemetry\Sdk\Trace\Clock;
use OpenTelemetry\Sdk\Trace\Sampler\AlwaysOnSampler;
use OpenTelemetry\Sdk\Trace\SamplingResult;
use OpenTelemetry\Sdk\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\Sdk\Trace\TracerProvider;
use OpenTelemetry\Trace as API;

$sampler = new AlwaysOnSampler();
$samplingResult = $sampler->shouldSample(
    null,
    md5((string) microtime(true)),
    substr(md5((string) microtime(true)), 16),
    'io.opentelemetry.example',
    API\SpanKind::KIND_INTERNAL
);
$Exporter = new OTLPExporter(
    'OTLP Grpc Example Service'
);

if (SamplingResult::RECORD_AND_SAMPLED === $samplingResult->getDecision()) {
    echo 'Starting OTLPGrpcExample';
    $tracer = (new TracerProvider())
        ->addSpanProcessor(new SimpleSpanProcessor($Exporter))
        ->getTracer('io.opentelemetry.contrib.php');

    for ($i = 0; $i < 5; $i++) {
        // start a span, register some events
        $timestamp = Clock::get()->timestamp();
        $span = $tracer->startAndActivateSpan('session.generate.span.' . microtime(true));

        $spanParent = $span->getParent();
        echo sprintf(
            PHP_EOL . 'Exporting Trace: %s, Parent: %s, Span: %s',
            $span->getContext()->getTraceId(),
            $spanParent ? $spanParent->getSpanId() : 'None',
            $span->getContext()->getSpanId()
        );

        $span->setAttribute('remote_ip', '1.2.3.4')
            ->setAttribute('country', 'USA');

        $span->addEvent('found_login' . $i, $timestamp, new Attributes([
            'id' => $i,
            'username' => 'otuser' . $i,
        ]));
        $span->addEvent('generated_session', $timestamp, new Attributes([
            'id' => md5((string) microtime(true)),
        ]));

        $tracer->endActiveSpan();
    }
    echo PHP_EOL . 'OTLPGrpcExample complete!  ';
} else {
    echo PHP_EOL . 'OTLPGrpcExample tracing is not enabled';
}

echo PHP_EOL;
