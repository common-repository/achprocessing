az acr login -n achdocker2

docker ps -a | awk '{ print $1,$2 }' | grep ach-auto | awk '{print $1 }' | xargs -I {} docker rm {} -f
docker pull achdocker2.azurecr.io/ach-auto-dev:$(Build.BuildId)

docker run -d --restart unless-stopped --name automation \
  -p 9003:5001 \
  -e "ConnectionStrings:Email"="$(db_Email)" \
  -e "ConnectionStrings:V2"="$(db_V2)" \
  -e "ConnectionStrings:V2Support"="$(db_SUPPORT)" \
  -e "ConnectionStrings:IdentityVerification"="$(db_IdentityVerification)" \
  -e "ConnectionStrings:AzureConnectionString"="$(db_AzureStorage)" \
\
  -e "Azure_Storage:shareName"="$(dbAzureStorageShareName)" \
\
  -e "JobConfigName"="$(jobsrcd_pname)" \
  -e "OFAC_SDN_URL"="$(OFAC_SDN_URL)" \
  -e "EPRD_ADS_URL"="$(EPRD_ADS_URL)" \
  -e "EPRD_RDN_NUM"="$(EPRD_RDN_NUM)" \
  -e "EPRD_DWL_NUM"="$(EPRD_DWL_NUM)" \
  -e "Force_SendTo"="achpcdev@harakirimail.com" \
\
  -e "TempFolder:Certificate"="$(certs_dir)" \
  -e "TempFolder:Inbound"="$(temp_inbound_dir)" \
  -e "TempFolder:Outbound"="$(temp_outbound_dir)" \
  -e "Ml:BaseUrl"="$(ml_base_url)" \
  -e "Ml:DA_ApiPath"="$(ml_da_api_path)" \
  -e "Ml:BaseLegacyUrl"="$(ml_base_legacy_url)" \
  -e "Ml:Austin_ApiPath"="$(ml_austin_api_path)" \
  -e "AchCoreBaseUrl"="$(achcore_base_url)" \ 
  -e "ReturnFilesLocalFolder"="$(ReturnFilesLocalFolder)" \

\
  achdocker2.azurecr.io/ach-auto-dev:$(Build.BuildId)