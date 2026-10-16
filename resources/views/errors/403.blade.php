@extends('errors.minimal')

@section('title', lexicon('label_common_Error403Title', 'Forbidden'))
@section('code', '403')
@section('message', !empty($exception?->getMessage()) ? $exception->getMessage() : lexicon('msg_common_Error403Message', 'Forbidden'))