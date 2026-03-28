{{- define "core-service-sandbox.fullname" -}}
{{- if .Values.fullnameOverride -}}
{{- .Values.fullnameOverride | trunc 63 | trimSuffix "-" -}}
{{- else -}}
{{- .Release.Name | trunc 63 | trimSuffix "-" -}}
{{- end -}}
{{- end -}}

{{- define "core-service-sandbox.selectorLabels" -}}
app.kubernetes.io/instance: {{ .Release.Name }}
app.kubernetes.io/name: {{ include "core-service-sandbox.fullname" . }}
{{- end -}}

{{- define "core-service-sandbox.componentSelectorLabels" -}}
app.kubernetes.io/component: {{ .component }}
{{ include "core-service-sandbox.selectorLabels" .root }}
{{- end -}}

{{- define "core-service-sandbox.labels" -}}
app.kubernetes.io/managed-by: {{ .Release.Service }}
app.kubernetes.io/part-of: core-service-sandbox
app.kubernetes.io/version: {{ .Chart.AppVersion | quote }}
sandbox.vilnacrm.com/id: {{ .Values.sandboxId | quote }}
{{ include "core-service-sandbox.selectorLabels" . }}
{{- end -}}

{{- define "core-service-sandbox.componentLabels" -}}
{{ include "core-service-sandbox.labels" .root }}
app.kubernetes.io/component: {{ .component }}
{{- end -}}

{{- define "core-service-sandbox.envSecretName" -}}
{{- printf "%s-env" (include "core-service-sandbox.fullname" .) -}}
{{- end -}}

{{- define "core-service-sandbox.httpServiceName" -}}
{{- printf "%s-http" (include "core-service-sandbox.fullname" .) -}}
{{- end -}}

{{- define "core-service-sandbox.mongodbServiceName" -}}
{{- printf "%s-mongodb" (include "core-service-sandbox.fullname" .) -}}
{{- end -}}

{{- define "core-service-sandbox.redisServiceName" -}}
{{- printf "%s-redis" (include "core-service-sandbox.fullname" .) -}}
{{- end -}}

{{- define "core-service-sandbox.localstackServiceName" -}}
{{- printf "%s-localstack" (include "core-service-sandbox.fullname" .) -}}
{{- end -}}
